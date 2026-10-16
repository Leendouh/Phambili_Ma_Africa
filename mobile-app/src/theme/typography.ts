export const typoSizes = {
  h1: 28,
  h2: 24,
  h3: 20,
  h4: 18,
  body: 16,
  bodySmall: 14,
  caption: 12,
  tiny: 11,
};

export const lineHeights = {
  tight: 1.25,
  normal: 1.5,
  relaxed: 1.6,
};

export const fontWeights = {
  regular: '400' as const,
  medium: '500' as const,
  semibold: '600' as const,
  bold: '700' as const,
};

export const typography = {
  sizes: typoSizes,
  lineHeights,
  fontWeights,
};
