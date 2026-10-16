export const fontFamily = {
  heading: 'Poppins_700Bold',
  headingMedium: 'Poppins_600SemiBold',
  body: 'OpenSans_400Regular',
  bodyBold: 'OpenSans_600SemiBold',
};

export const typoSizes = {
  display: 32,
  h1: 26,
  h2: 22,
  h3: 18,
  h4: 16,
  body: 15,
  bodySmall: 13,
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
  family: fontFamily,
  sizes: typoSizes,
  lineHeights,
  fontWeights,
};
