# Verdant Design System

Verdant is a premium, high-precision design system built for professional assessment platforms. It prioritizes perceptual uniformity, editorial typography, and organic, matcha-inspired tones.

## 🌿 Philosophy

- **Organic Precision**: A balance between technical accuracy and a natural, grounded aesthetic.
- **Editorial Feel**: Using high-serif display fonts to give the platform a curated, "thought-leadership" quality.
- **Perceptual Uniformity**: All colors are defined in **OKLCH** to ensure consistent brightness and chroma transitions across both light and dark modes.

## 🎨 Color Palette (OKLCH)

The system uses a signature "Verdant Green" ( Japanese Matcha) as its primary identity.

### Primary: Verdant
| Stop | L (Lightness) | C (Chroma) | H (Hue) | Usage |
|------|---|---|---|---|
| 50   | 0.98 | 0.01 | 135 | Ultra-subtle background |
| 400  | 0.70 | 0.08 | 135 | Secondary highlights |
| 500  | 0.58 | 0.09 | 135 | Primary actions / UI Dot |
| 600  | 0.46 | 0.08 | 135 | Dark Primary / Deep button |
| 950  | 0.06 | 0.02 | 135 | Text in dark mode / Depth |

### Accents
- **Lichen (H=120)**: Muted moss green for secondary surfaces.
- **Aurora (H=180)**: Teal-cyan for info states and gradients.
- **Citron (H=100)**: Warm yellow-green for warnings and progress.
- **Coral (H=25)**: Deep coral-red for errors and destructive actions.

### Neutrals: Stone (H=135)
Warm neutrals that share the green hue's DNA but with extremely low chroma (C=0.01).

## 🖋️ Typography

- **Display**: [Instrument Serif](https://fonts.google.com/specimen/Instrument+Serif). Used for headlines (H1-H4), Hero text, and key branding.
- **UI/Body**: [Inter Variable](https://fonts.google.com/specimen/Inter). Used for all interface text, labels, and paragraph content.
- **Code**: [JetBrains Mono](https://fonts.google.com/specimen/JetBrains+Mono). Used for code blocks, inputs, and technical meta-data.

### Typographic Scale
- `display-2xl`: 4.5rem (72px)
- `h1`: 2.25rem (36px)
- `body`: 1rem (16px)
- `caption`: 0.75rem (12px)

## ✨ Motion

Motion in Verdant is deliberate and weighted, using custom cubic-beziers.

- **Easings**:
  - `ease-verdant-in`: `cubic-bezier(0.4, 0, 0.2, 1)`
  - `ease-spring-soft`: `cubic-bezier(0.34, 1.56, 0.64, 1)`
- **Standard Durations**: 
  - `fast`: 200ms
  - `base`: 300ms
  - `deliberate`: 800ms

## 🏗️ Structure

- **CSS**: `resources/css/verdant.css`
- **JS Tokens**: `resources/js/lib/verdant/tokens.ts`
- **Motion Presets**: `resources/js/lib/verdant/motion.ts`
