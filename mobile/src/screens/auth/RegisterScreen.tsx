import React, { useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  Alert,
  KeyboardAvoidingView,
  Platform,
  ScrollView,
  TouchableOpacity,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useAuth } from '../../context/AuthContext';
import {
  validateEmail,
  validateSAPhone,
  validatePassword,
  validateRequired,
} from '../../utils/validators';
import { RegisterFormData } from '../../types';
import { AppButton, AppInput } from '../../components/ui';
import { lightColors as C } from '../../theme/colors';
import { fontFamily } from '../../theme/typography';
import { radii } from '../../theme/radii';
import { spacing } from '../../theme/spacing';

export default function RegisterScreen({ navigation }: any) {
  const insets = useSafeAreaInsets();
  const { register, loading: authLoading } = useAuth();
  const [formData, setFormData] = useState<RegisterFormData>({
    firstName: '',
    lastName: '',
    email: '',
    phone: '',
    password: '',
    address: '',
  });
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [loading, setLoading] = useState(false);
  const [showPassword, setShowPassword] = useState(false);

  const validateForm = () => {
    const newErrors: Record<string, string> = {};

    const firstNameError = validateRequired(formData.firstName, 'First name');
    if (firstNameError) newErrors.firstName = firstNameError;

    const lastNameError = validateRequired(formData.lastName, 'Last name');
    if (lastNameError) newErrors.lastName = lastNameError;

    const emailError = validateEmail(formData.email);
    if (emailError) newErrors.email = emailError;

    const phoneError = validateSAPhone(formData.phone);
    if (phoneError) newErrors.phone = phoneError;

    const passwordError = validatePassword(formData.password);
    if (passwordError) newErrors.password = passwordError;

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleRegister = async () => {
    if (!validateForm()) return;

    setLoading(true);
    try {
      await register(formData);
      Alert.alert(
<<<<<<< HEAD
        'Registration Successful',
        'Your account has been created. You can now log in.',
        [{ text: 'OK', onPress: () => navigation.navigate('Login') }]
=======
        'Welcome to Phambili Ma-Africa',
        `Your account is ready, ${formData.firstName}. Pick a service to make your first request.`
>>>>>>> 6f19b56de913460717b19fe32251bb23c912f548
      );
    } catch (error: any) {
      Alert.alert('Registration Failed', error.message);
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
        <View style={[styles.hero, { paddingTop: insets.top + spacing.md }]}>
          <TouchableOpacity
            style={styles.backButton}
            onPress={() => navigation.goBack()}
            hitSlop={{ top: 10, bottom: 10, left: 10, right: 10 }}
          >
            <Ionicons name="arrow-back" size={22} color={C.textOnPrimary} />
          </TouchableOpacity>
          <Text style={styles.heroTitle}>Create account</Text>
          <Text style={styles.heroSubtitle}>Register as a customer to request services</Text>
        </View>

        <View style={styles.body}>
          <View style={styles.row}>
            <View style={styles.half}>
              <AppInput
                label="First name"
                placeholder="John"
                value={formData.firstName}
                onChangeText={(text) => {
                  setFormData({ ...formData, firstName: text });
                  if (errors.firstName) setErrors({ ...errors, firstName: '' });
                }}
                autoCapitalize="words"
                error={errors.firstName}
              />
            </View>
            <View style={styles.half}>
              <AppInput
                label="Last name"
                placeholder="Doe"
                value={formData.lastName}
                onChangeText={(text) => {
                  setFormData({ ...formData, lastName: text });
                  if (errors.lastName) setErrors({ ...errors, lastName: '' });
                }}
                autoCapitalize="words"
                error={errors.lastName}
              />
            </View>
          </View>

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
            label="Phone number"
            icon="call-outline"
            placeholder="+27 12 345 6789"
            value={formData.phone}
            onChangeText={(text) => {
              setFormData({ ...formData, phone: text });
              if (errors.phone) setErrors({ ...errors, phone: '' });
            }}
            keyboardType="phone-pad"
            autoComplete="tel"
            error={errors.phone}
          />

          <AppInput
            label="Address (optional)"
            icon="location-outline"
            placeholder="Your address"
            value={formData.address}
            onChangeText={(text) => setFormData({ ...formData, address: text })}
            autoCapitalize="sentences"
          />

          <AppInput
            label="Password"
            icon="lock-closed-outline"
            placeholder="Create a password"
            value={formData.password}
            onChangeText={(text) => {
              setFormData({ ...formData, password: text });
              if (errors.password) setErrors({ ...errors, password: '' });
            }}
            secureTextEntry={!showPassword}
            autoComplete="password-new"
            error={errors.password}
            rightIcon={showPassword ? 'eye-off-outline' : 'eye-outline'}
            onRightIconPress={() => setShowPassword(!showPassword)}
          />

          <AppButton
            title="Create Account"
            loading={loading || authLoading}
            onPress={handleRegister}
          />

          <View style={styles.loginRow}>
            <Text style={styles.loginText}>Already have an account? </Text>
            <Text style={styles.loginLink} onPress={() => navigation.navigate('Login')}>
              Sign in
            </Text>
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
    paddingHorizontal: spacing.xl,
    paddingBottom: spacing.xxl,
    borderBottomLeftRadius: radii.xl + 8,
    borderBottomRightRadius: radii.xl + 8,
  },
  backButton: {
    width: 38,
    height: 38,
    borderRadius: 19,
    backgroundColor: C.primarySoft,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: spacing.lg,
  },
  heroTitle: {
    fontFamily: fontFamily.heading,
    fontSize: 24,
    color: C.textOnPrimary,
  },
  heroSubtitle: {
    fontFamily: fontFamily.body,
    fontSize: 13,
    color: C.textOnPrimaryMuted,
    marginTop: 6,
  },
  body: {
    paddingHorizontal: spacing.xl,
    paddingTop: spacing.xxl,
    paddingBottom: spacing.xxxl,
  },
  row: {
    flexDirection: 'row',
    gap: spacing.md,
  },
  half: {
    flex: 1,
  },
  loginRow: {
    flexDirection: 'row',
    justifyContent: 'center',
    marginTop: spacing.xxl,
  },
  loginText: {
    fontFamily: fontFamily.body,
    fontSize: 14,
    color: C.textLight,
  },
  loginLink: {
    fontFamily: fontFamily.bodyBold,
    fontSize: 14,
    color: C.primary,
  },
});
