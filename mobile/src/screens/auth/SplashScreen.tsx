import React, { useEffect, useState } from 'react';
import { ActivityIndicator, Animated, StyleSheet, Text, View } from 'react-native';
import { lightColors as C } from '../../theme/colors';
import { fontFamily } from '../../theme/typography';
import { spacing } from '../../theme/spacing';
import { radii } from '../../theme/radii';

export default function SplashScreen() {
  const [fade] = useState(() => new Animated.Value(0));
  const [scale] = useState(() => new Animated.Value(0.92));

  useEffect(() => {
    Animated.parallel([
      Animated.timing(fade, { toValue: 1, duration: 500, useNativeDriver: true }),
      Animated.spring(scale, { toValue: 1, friction: 6, useNativeDriver: true }),
    ]).start();
  }, [fade, scale]);

  return (
    <View style={styles.container}>
      <View style={styles.circleA} />
      <View style={styles.circleB} />
      <View style={styles.circleC} />
      <View style={styles.circleD} />
      <Animated.View style={[styles.brand, { opacity: fade, transform: [{ scale }] }]}>
        <View style={styles.brandMark}>
          <Text style={styles.brandMarkText}>PM</Text>
        </View>
        <Text style={styles.brandName}>Phambili Ma-Africa</Text>
        <Text style={styles.brandTagline}>Service requests, managed professionally</Text>
      </Animated.View>
      <ActivityIndicator style={styles.spinner} color={C.accent} />
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: C.primary,
    alignItems: 'center',
    justifyContent: 'center',
    overflow: 'hidden',
  },
  circleA: {
    position: 'absolute',
    top: -90,
    right: -70,
    width: 260,
    height: 260,
    borderRadius: 130,
    backgroundColor: 'rgba(217,160,63,0.18)',
  },
  circleB: {
    position: 'absolute',
    bottom: -110,
    left: -80,
    width: 300,
    height: 300,
    borderRadius: 150,
    backgroundColor: 'rgba(14,159,138,0.15)',
  },
  circleC: {
    position: 'absolute',
    top: '28%',
    left: -50,
    width: 130,
    height: 130,
    borderRadius: 65,
    backgroundColor: 'rgba(28,143,224,0.16)',
  },
  circleD: {
    position: 'absolute',
    bottom: '22%',
    right: -40,
    width: 110,
    height: 110,
    borderRadius: 55,
    backgroundColor: 'rgba(123,92,240,0.16)',
  },
  brand: {
    alignItems: 'center',
  },
  brandMark: {
    width: 88,
    height: 88,
    borderRadius: radii.lg * 1.5,
    backgroundColor: C.accent,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: spacing.lg,
  },
  brandMarkText: {
    fontFamily: fontFamily.heading,
    fontSize: 34,
    color: C.primary,
  },
  brandName: {
    fontFamily: fontFamily.heading,
    fontSize: 28,
    color: C.textOnPrimary,
  },
  brandTagline: {
    fontFamily: fontFamily.body,
    fontSize: 14,
    color: C.textOnPrimaryMuted,
    marginTop: spacing.sm,
  },
  spinner: {
    position: 'absolute',
    bottom: spacing.huge * 2,
  },
});
