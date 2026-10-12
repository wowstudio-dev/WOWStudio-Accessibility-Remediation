"""
Builds the WordPress.org icon and banner from the plugin's own mark and palette.

Checked in so these can be regenerated rather than redrawn. Every colour is a
token from assets/src/style.scss and the zigzag is the exact path
assets/src/app.js draws in the admin header — an icon that does not match the
screen it opens is a small lie told at the first moment somebody meets the
plugin, and a hand-drawn copy drifts the moment the palette moves.

Output goes to .wordpress-org/, which .distignore keeps out of the plugin zip:
WordPress.org reads these from the assets/ directory beside the plugin folder
in SVN, not from inside it.

Development-only, and deliberately outside the toolchain the gate runs: it needs
Python with Pillow, and it reads Helvetica Neue from a macOS system path with an
Arial fallback. Nothing in the plugin depends on it.

    python3 bin/make-directory-art.py
"""

import math
import os
from PIL import Image, ImageDraw, ImageFont

# Relative to this file, not an absolute path. The absolute one was rewritten
# by the 1.0.1 rename sweep, which is exactly the failure mode hard-coding a
# path invites.
OUT = os.path.join(os.path.dirname(os.path.abspath(__file__)), "..", ".wordpress-org")

LAVENDER = (185, 169, 247)
VIOLET = (123, 92, 240)
INDIGO = (91, 63, 217)
NAVY = (42, 38, 82)
WHITE = (255, 255, 255)

SS = 4  # supersample factor

# The mark, exactly as the admin header draws it, in a 24x24 box.
MARK = [(3, 7.6), (7.2, 16.6), (12, 9.4), (16.8, 16.6), (21, 7.6)]
MARK_STROKE = 2.6


def lerp(a, b, t):
    return tuple(round(a[i] + (b[i] - a[i]) * t) for i in range(3))


def gradient(size, stops, angle_deg):
    """A linear gradient with positioned stops, at an angle in degrees."""
    w, h = size
    img = Image.new("RGB", size)
    px = img.load()
    rad = math.radians(angle_deg)
    dx, dy = math.cos(rad), math.sin(rad)
    # Project every corner so the ramp spans the whole box.
    projections = [x * dx + y * dy for x in (0, w) for y in (0, h)]
    lo, hi = min(projections), max(projections)
    span = (hi - lo) or 1

    for y in range(h):
        for x in range(w):
            t = ((x * dx + y * dy) - lo) / span
            for i in range(len(stops) - 1):
                p0, c0 = stops[i]
                p1, c1 = stops[i + 1]
                if p0 <= t <= p1:
                    local = (t - p0) / ((p1 - p0) or 1)
                    px[x, y] = lerp(c0, c1, local)
                    break
            else:
                px[x, y] = stops[-1][1] if t > stops[-1][0] else stops[0][1]
    return img


def rounded_mask(size, radius):
    mask = Image.new("L", size, 0)
    ImageDraw.Draw(mask).rounded_rectangle(
        (0, 0, size[0] - 1, size[1] - 1), radius=radius, fill=255
    )
    return mask


def draw_mark(draw, box, colour, stroke_scale=1.0):
    """Draws the zigzag inside `box` = (x, y, side), with round caps and joins."""
    x, y, side = box
    unit = side / 24.0
    pts = [(x + px * unit, y + py * unit) for px, py in MARK]
    width = max(1, round(MARK_STROKE * unit * stroke_scale))

    draw.line(pts, fill=colour, width=width, joint="curve")

    # joint="curve" rounds the joins but leaves butt caps; these are the caps.
    r = width / 2
    for cx, cy in (pts[0], pts[-1]):
        draw.ellipse((cx - r, cy - r, cx + r, cy + r), fill=colour)


# Helvetica Neue by index, because the .ttc holds fourteen faces and asking for
# the wrong number silently gives you an oblique one.
FACES = {
    "regular": 0,
    "bold": 1,
    "medium": 10,
    "light": 7,
}


def helvetica(size, weight="bold"):
    try:
        return ImageFont.truetype(
            "/System/Library/Fonts/HelveticaNeue.ttc", size, index=FACES[weight]
        )
    except Exception:
        upright = {
            "bold": "Arial Bold.ttf",
            "medium": "Arial Bold.ttf",
            "regular": "Arial.ttf",
            "light": "Arial.ttf",
        }[weight]
        return ImageFont.truetype(f"/System/Library/Fonts/Supplemental/{upright}", size)


def build_icon(px):
    size = (px * SS, px * SS)
    base = gradient(size, [(0.0, LAVENDER), (0.45, VIOLET), (1.0, INDIGO)], 20)

    layer = Image.new("RGBA", size, (0, 0, 0, 0))
    draw = ImageDraw.Draw(layer)
    side = size[0] * 0.62
    draw_mark(draw, ((size[0] - side) / 2, (size[1] - side) / 2, side), WHITE + (255,), 1.05)

    icon = Image.new("RGBA", size, (0, 0, 0, 0))
    icon.paste(base, (0, 0))
    icon = Image.alpha_composite(icon.convert("RGBA"), layer)
    icon.putalpha(rounded_mask(size, radius=round(size[0] * 0.22)))

    return icon.resize((px, px), Image.LANCZOS)


def chip(draw, x, y, text, font, pad_x, pad_y, radius):
    """A pill. Returns the x to start the next one at."""
    w = draw.textlength(text, font=font)
    top = draw.textbbox((0, 0), text, font=font)[1]
    box = (x, y, x + w + pad_x * 2, y + pad_y * 2 + font.size)
    draw.rounded_rectangle(box, radius=radius, fill=(255, 255, 255, 20), outline=LAVENDER + (70,), width=max(1, round(radius * 0.08)))
    draw.text((x + pad_x, y + pad_y - top + (font.size - (draw.textbbox((0, 0), text, font=font)[3] - top)) / 2), text, font=font, fill=LAVENDER + (255,))
    return box[2]


def build_banner(w, h):
    """
    The banner, which deliberately does not say the plugin's name.

    The directory prints the name as a heading immediately underneath this
    image, so a banner that repeats it spends its only moment of attention
    saying something the page already said. It carries the argument instead:
    what this plugin does that the other ones in the same search do not.

    Everything sits in the left two thirds. The directory's own layout puts
    nothing over the banner, but the 772-wide version is what most people
    actually see, and type that survives at that width has to be large.
    """
    size = (w * 2, h * 2)
    img = gradient(size, [(0.0, NAVY), (0.55, (58, 48, 112)), (1.0, INDIGO)], 12)

    # A soft wash of the lavender end, kept well away from any text.
    glow = Image.new("RGBA", size, (0, 0, 0, 0))
    gd = ImageDraw.Draw(glow)
    for i in range(60, 0, -1):
        r = size[1] * (0.30 + i * 0.016)
        gd.ellipse(
            (size[0] - r * 0.75, -r * 0.45, size[0] + r * 0.9, size[1] + r * 0.5),
            fill=LAVENDER + (2,),
        )
    img = Image.alpha_composite(img.convert("RGBA"), glow)
    draw = ImageDraw.Draw(img, "RGBA")

    pad = size[1] * 0.17
    tile = size[1] * 0.30

    tile_img = build_icon(round(tile))
    img.paste(tile_img, (round(pad), round(size[1] * 0.175)), tile_img)

    x = pad + tile + size[1] * 0.10

    # A focus ring around the mark. It is decoration that happens to be true:
    # a visible focus outline is one of the site-wide fixes this plugin adds,
    # and it is the single most recognisable image accessibility work has.
    ring = size[1] * 0.045
    draw.rounded_rectangle(
        (
            pad - ring,
            size[1] * 0.175 - ring,
            pad + tile + ring,
            size[1] * 0.175 + tile + ring,
        ),
        radius=round(tile * 0.22 + ring),
        outline=LAVENDER + (170,),
        width=max(2, round(size[1] * 0.013)),
    )

    head = helvetica(round(size[1] * 0.168), "bold")
    chips = helvetica(round(size[1] * 0.056), "medium")

    draw.text((x, size[1] * 0.195), "Fix the page,", font=head, fill=WHITE + (255,))
    draw.text((x, size[1] * 0.395), "not the symptom.", font=head, fill=WHITE + (255,))

    cx = x
    for label in ("41 WCAG CHECKS", "NOT AN OVERLAY", "NO TRACKING", "FREE"):
        cx = chip(
            draw,
            cx,
            size[1] * 0.665,
            label,
            chips,
            pad_x=size[1] * 0.045,
            pad_y=size[1] * 0.030,
            radius=size[1] * 0.055,
        ) + size[1] * 0.030

    return img.convert("RGB").resize((w, h), Image.LANCZOS)


for px in (256, 128):
    build_icon(px).save(f"{OUT}/icon-{px}x{px}.png")
    print(f"icon-{px}x{px}.png")

for w, h in ((1544, 500), (772, 250)):
    build_banner(w, h).save(f"{OUT}/banner-{w}x{h}.png")
    print(f"banner-{w}x{h}.png")
