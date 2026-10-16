import { useColorScheme } from 'react-native';
import { lightColors, darkColors, type Colors } from './colors';
import { typography } from './typography';
import { spacing } from './spacing';
import { radii } from './radii';
import { shadows } from './shadows';

export function useTheme() {
  const colorScheme = useColorScheme();
  const isDark = colorScheme === 'dark';
  
  const colors = isDark ? darkColors : lightColors;
  
  return {
    colors,
    typography,
    spacing,
    radii,
    shadows,
    isDark,
  };
}

export type { Colors };
