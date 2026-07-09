"""
Conversor CLIP → ONNX int8 (se ejecuta UNA sola vez, en una máquina con PyTorch).

Exporta el codificador de imágenes de `openai/clip-vit-base-patch32` a ONNX,
lo cuantiza a int8 y VERIFICA que los vectores resultantes siguen siendo
equivalentes a los de PyTorch (similitud coseno ~1.0). El archivo generado
(`models/clip_image_int8.onnx`) es lo único que necesita el servidor en
producción, donde solo se instala `onnxruntime` (sin PyTorch → ~400 MB de RAM
en lugar de ~2 GB).

Uso:
    cd backend
    python -m venv .venv-export
    .venv-export\\Scripts\\activate        (Windows)  |  source .venv-export/bin/activate
    pip install -r requirements-export.txt
    python scripts/export_clip_onnx.py

Luego sube `backend/models/clip_image_int8.onnx` al servidor.
"""

import os
import numpy as np
from PIL import Image

import torch
from transformers import CLIPModel, CLIPProcessor
from onnxruntime.quantization import quantize_dynamic, QuantType
import onnxruntime as ort

MODEL_ID   = "openai/clip-vit-base-patch32"
OUT_DIR    = os.path.join(os.path.dirname(os.path.dirname(os.path.abspath(__file__))), "models")
FP32_PATH  = os.path.join(OUT_DIR, "clip_image_fp32.onnx")
INT8_PATH  = os.path.join(OUT_DIR, "clip_image_int8.onnx")

# Constantes de preprocesamiento de CLIP (idénticas a las de CLIPImageProcessor).
# El servicio en producción las replica en numpy, sin `transformers`.
CLIP_MEAN = np.array([0.48145466, 0.4578275, 0.40821073], dtype=np.float32)
CLIP_STD  = np.array([0.26862954, 0.26130258, 0.27577711], dtype=np.float32)
TARGET    = 224


# ── Preprocesamiento manual (el MISMO que usará clip_service.py) ──────────────
def preprocess_numpy(image: Image.Image) -> np.ndarray:
    """Resize (borde corto→224, bicúbico) + center-crop 224 + normalización CLIP."""
    image = image.convert("RGB")
    w, h = image.size
    scale = TARGET / min(w, h)
    new_w, new_h = round(w * scale), round(h * scale)
    image = image.resize((new_w, new_h), Image.BICUBIC)
    left = (new_w - TARGET) // 2
    top  = (new_h - TARGET) // 2
    image = image.crop((left, top, left + TARGET, top + TARGET))
    arr = np.asarray(image, dtype=np.float32) / 255.0        # HWC RGB
    arr = (arr - CLIP_MEAN) / CLIP_STD
    arr = arr.transpose(2, 0, 1)                             # CHW
    return arr[np.newaxis, ...].astype(np.float32)           # NCHW


class ClipImageEncoder(torch.nn.Module):
    """Envuelve get_image_features + normalización L2 para exportar un grafo limpio."""
    def __init__(self, model):
        super().__init__()
        self.model = model

    def forward(self, pixel_values):
        feats = self.model.get_image_features(pixel_values=pixel_values)
        return torch.nn.functional.normalize(feats, p=2, dim=-1)


def main():
    os.makedirs(OUT_DIR, exist_ok=True)

    print("⏳ Cargando CLIP desde HuggingFace…")
    model     = CLIPModel.from_pretrained(MODEL_ID).eval()
    processor = CLIPProcessor.from_pretrained(MODEL_ID)
    wrapper   = ClipImageEncoder(model).eval()

    # ── 1) Exportar a ONNX (fp32) ────────────────────────────────────────────
    dummy = torch.randn(1, 3, TARGET, TARGET)
    print(f"📤 Exportando a ONNX → {FP32_PATH}")
    torch.onnx.export(
        wrapper, dummy, FP32_PATH,
        input_names=["pixel_values"], output_names=["image_embeds"],
        dynamic_axes={"pixel_values": {0: "batch"}, "image_embeds": {0: "batch"}},
        opset_version=17,
    )

    # ── 2) Cuantizar a int8 ──────────────────────────────────────────────────
    print(f"🗜️  Cuantizando a int8 → {INT8_PATH}")
    quantize_dynamic(FP32_PATH, INT8_PATH, weight_type=QuantType.QInt8)
    mb = os.path.getsize(INT8_PATH) / 1e6
    print(f"   Tamaño del modelo int8: {mb:.1f} MB")

    # ── 3) Verificación de paridad (imagen de prueba) ────────────────────────
    print("\n🔎 Verificando paridad PyTorch vs ONNX int8…")
    test_img = Image.fromarray((np.random.rand(400, 640, 3) * 255).astype("uint8"))

    # (a) preprocesamiento manual (numpy) vs CLIPProcessor
    px_manual = preprocess_numpy(test_img)
    px_hf = processor(images=test_img, return_tensors="pt")["pixel_values"].numpy()
    max_diff = float(np.abs(px_manual - px_hf).max())
    print(f"   Δ preprocesamiento (manual vs CLIPProcessor): {max_diff:.5f}  (ideal < 0.05)")

    # (b) embedding PyTorch (con su propio preprocesamiento) vs ONNX int8 (numpy)
    with torch.no_grad():
        emb_torch = wrapper(torch.from_numpy(px_hf)).numpy()[0]

    sess = ort.InferenceSession(INT8_PATH, providers=["CPUExecutionProvider"])
    in_name = sess.get_inputs()[0].name
    emb_onnx = sess.run(None, {in_name: px_manual})[0][0]
    emb_onnx = emb_onnx / (np.linalg.norm(emb_onnx) + 1e-12)

    cos = float(np.dot(emb_torch, emb_onnx))
    print(f"   Similitud coseno (PyTorch vs ONNX int8): {cos:.4f}  (ideal > 0.99)")

    if cos > 0.98:
        print("\n✅ Conversión válida. Sube 'backend/models/clip_image_int8.onnx' al servidor.")
    else:
        print("\n⚠️ La similitud es baja. Revisa el preprocesamiento antes de usar en producción.")

    # Limpieza del fp32 intermedio (solo se necesita el int8)
    try:
        os.remove(FP32_PATH)
    except OSError:
        pass


if __name__ == "__main__":
    main()
