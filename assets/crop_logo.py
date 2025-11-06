from pathlib import Path
from PIL import Image
import numpy as np

src = Path(r"C:\Users\Nha My\Downloads\bttl\assets\logo.jpg")
if not src.exists():
    print('Source not found:', src)
    raise SystemExit(1)

out_png = src.with_name('logo_cropped.png')

img = Image.open(src).convert('RGBA')
arr = np.array(img)
# arr: H x W x 4
rgb = arr[:, :, :3]
# consider a pixel as non-white if any channel is <= threshold
threshold = 250
mask = np.any(rgb < threshold, axis=2)

if not mask.any():
    # try lower threshold
    threshold = 245
    mask = np.any(rgb < threshold, axis=2)

if not mask.any():
    print('Could not detect non-white pixels. The image may be fully white or threshold too low.')
    # save original as png
    img.save(out_png)
    print('Saved original as', out_png)
    raise SystemExit(0)

ys, xs = np.where(mask)
miny, maxy = ys.min(), ys.max()
minx, maxx = xs.min(), xs.max()

# Crop with a 1px padding (optional)
pad = 1
minx = max(minx - pad, 0)
miny = max(miny - pad, 0)
maxx = min(maxx + pad, arr.shape[1] - 1)
maxy = min(maxy + pad, arr.shape[0] - 1)

cropped = img.crop((minx, miny, maxx + 1, maxy + 1))
# Create new image with transparency where background was white
cropped_arr = np.array(cropped)
# Recompute mask on cropped
cropped_rgb = cropped_arr[:, :, :3]
mask_c = np.any(cropped_rgb < threshold, axis=2)
alpha = (mask_c * 255).astype(np.uint8)
# set alpha channel
cropped_arr[:, :, 3] = alpha
out_img = Image.fromarray(cropped_arr)
out_img.save(out_png)

print('Saved cropped logo to', out_png)
print('Size:', out_png.stat().st_size, 'bytes')
print('Cropped dimensions:', out_img.size)
