import React, { useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  Alert,
  KeyboardAvoidingView,
  Platform,
  ScrollView,
} from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useAuth } from '../../context/AuthContext';
import { validateEmail } from '../../utils/validators';
import { LoginFormData } from '../../types';
import { AppButton, AppInput } from '../../components/ui';
import { lightColors as C } from '../../theme/colors';
import { fontFamily } from '../../theme/typography';
import { radii } from '../../theme/radii';
import { spacing } from '../../theme/spacing';

export default function LoginScreen({ navigation }: any) {
  const insets = useSafeAreaInsets();
  const { login, loading: authLoading } = useAuth();
  const [formData, setFormData] = useState<LoginFormData>({
    email: '',
    password: '',
  });
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [loading, setLoading] = useState(false);
  const [showPassword, setShowPassword] = useState(false);

  const validateForm = () => {
    const newErrors: Record<string, string> = {};

    const emailError = validateEmail(formData.email);
    if (emailError) newErrors.email = emailError;

    if (!formData.password) newErrors.password = 'Password is required';

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleLogin = async () => {
    if (!validateForm()) return;

    setLoading(true);
    try {
      await login(formData.email, formData.password);
      // Navigation will be handled by the auth state change
    } catch (error: any) {
      Alert.alert('Login Failed', error.message);
    } finally {
      setLoading(false);
    }
  };

  return (
    <KeyboardAvoidingView
      style={styles.container}
      behavior={Platform.OS === 'ios' ? 'padding' : undefined}
    >
      <ScrollView
        contentContainerStyle={{ flexGrow: 1 }}
        keyboardShouldPersistTaps="handled"
        bounces={false}
      >
        <View style={[styles.hero, { paddingTop: insets.top + spacing.huge }]}>
          <View style={styles.brandMark}>
            <Text style={styles.brandMarkText}>PM</Text>
          </View>
          <Text style={styles.brandName}>Phambili Ma-Africa</Text>
          <Text style={styles.brandTagline}>Service requests, managed professionally</Text>
        </View>

        <View style={styles.body}>
          <Text style={styles.heading}>Welcome back</Text>
          <Text style={styles.subheading}>Sign in to continue to your account</Text>

          <View style={{ marginTop: spacing.xxl }}>
            <AppInput
              label="Email address"
              icon="mail-outline"
              placeholder="you@example.com"
              value={formData.email}
              onChangeText={(text) => {
                setFormData({ ...formData, email: text });
                if (errors.email) setErrors({ ...errors, email: '' });
              }}
              keyboardType="email-address"
              autoCapitalize="none"
              autoComplete="email"
              error={errors.email}
            />

            <AppInput
              label="Password"
              icon="lock-closed-outline"
              placeholder="Enter your password"
              value={formData.password}
              onChangeText={(text) => {
                setFormData({ ...formData, password: text });
                if (errors.password) setErrors({ ...errors, password: '' });
              }}
              secureTextEntry={!showPassword}
              autoComplete="password"
              error={errors.password}
              rightIcon={showPassword ? 'eye-off-outline' : 'eye-outline'}
              onRightIconPress={() => setShowPassword(!showPassword)}
            />

            <AppButton
              title="Sign In"
              loading={loading || authLoading}
              onPress={handleLogin}
            />

            <View style={styles.registerRow}>
              <Text style={styles.registerText}>{"Don't have an account? "}</Text>
              <Text
                style={styles.registerLink}
                onPress={() => navigation.navigate('Register')}
              >
                Create one
              </Text>
            </View>
          </View>
        </View>
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: C.background,
  },
  hero: {
    backgroundColor: C.primary,
    alignItems: 'center',
    paddingBottom: spacing.huge,
    borderBottomLeftRadius: radii.xl + 8,
    borderBottomRightRadius: radii.xl + 8,
  },
  brandMark: {
    width: 64,
    height: 64,
    borderRadius: radii.lg,
    backgroundColor: C.accent,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: spacing.lg,
  },
  brandMarkText: {
    fontFamily: fontFamily.heading,
    fontSize: 24,
    color: C.primary,
  },
  brandName: {
    fontFamily: fontFamily.heading,
    fontSize: 22,
    color: C.textOnPrimary,
  },
  brandTagline: {
    fontFamily: fontFamily.body,
    fontSize: 13,
    color: C.textOnPrimaryMuted,
    marginTop: 6,
  },
  body: {
    flex: 1,
    paddingHorizontal: spacing.xl,
    paddingTop: spacing.xxxl,
  },
  heading: {
    fontFamily: fontFamily.heading,
    fontSize: 26,
    color: C.text,
  },
  subheading: {
    fontFamily: fontFamily.body,
    fontSize: 14,
    color: C.textLight,
    marginTop: 6,
  },
  registerRow: {
    flexDirection: 'row',
    justifyContent: 'center',
    marginTop: spacing.xxl,
  },
  registerText: {
    fontFamily: fontFamily.body,
    fontSize: 14,
    color: C.textLight,
  },
  registerLink: {
    fontFamily: fontFamily.bodyBold,
    fontSize: 14,
    color: C.primary,
  },
});
