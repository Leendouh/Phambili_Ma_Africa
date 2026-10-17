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
import { updatePassword } from 'firebase/auth';
import { doc, updateDoc } from 'firebase/firestore';
import { auth, db } from '../../firebase';
import { useAuth } from '../../context/AuthContext';
import { getFirebaseErrorMessage } from '../../utils/errorHandler';
import { AppButton, AppInput } from '../../components/ui';
import { lightColors as C } from '../../theme/colors';
import { fontFamily } from '../../theme/typography';
import { radii } from '../../theme/radii';
import { spacing } from '../../theme/spacing';

// Shown when an admin-created account (employee/staff) signs in for the
// first time — they must replace the generated password before the app
// routes them anywhere. Clearing users/{uid}.firstLogin releases the gate
// in AuthNavigator and on the website's login.php.
export default function ForcePasswordScreen() {
  const insets = useSafeAreaInsets();
  const { userProfile, refreshProfile } = useAuth();
  const [password, setPassword] = useState('');
  const [confirm, setConfirm] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [loading, setLoading] = useState(false);

  const validate = () => {
    const e: Record<string, string> = {};
    if (
      password.length < 8 ||
      !/[A-Z]/.test(password) ||
      !/[a-z]/.test(password) ||
      !/[0-9]/.test(password) ||
      !/[!@#$%^&*]/.test(password)
    ) {
      e.password =
        'Min 8 characters with an uppercase letter, a lowercase letter, a number and a special character (!@#$%^&*)';
    }
    if (confirm !== password) e.confirm = 'Passwords do not match';
    setErrors(e);
    return Object.keys(e).length === 0;
  };

  const handleSubmit = async () => {
    if (!validate()) return;
    const user = auth.currentUser;
    if (!user) return;

    setLoading(true);
    try {
      await updatePassword(user, password);
      await updateDoc(doc(db, 'users', user.uid), { firstLogin: false });
      await refreshProfile();
      // Navigation switches automatically once the profile flag clears.
    } catch (error) {
      Alert.alert('Could not set password', getFirebaseErrorMessage(error));
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
          <Text style={styles.heading}>Set your password</Text>
          <Text style={styles.subheading}>
            {`Welcome${userProfile?.firstName ? `, ${userProfile.firstName}` : ''}. Your account was created by an administrator with a temporary password — choose your own to continue.`}
          </Text>

          <View style={{ marginTop: spacing.xxl }}>
            <AppInput
              label="New password"
              icon="lock-closed-outline"
              placeholder="Enter a new password"
              value={password}
              onChangeText={(text) => {
                setPassword(text);
                if (errors.password) setErrors({ ...errors, password: '' });
              }}
              secureTextEntry={!showPassword}
              autoComplete="new-password"
              error={errors.password}
              rightIcon={showPassword ? 'eye-off-outline' : 'eye-outline'}
              onRightIconPress={() => setShowPassword(!showPassword)}
            />

            <AppInput
              label="Confirm password"
              icon="lock-closed-outline"
              placeholder="Repeat your password"
              value={confirm}
              onChangeText={(text) => {
                setConfirm(text);
                if (errors.confirm) setErrors({ ...errors, confirm: '' });
              }}
              secureTextEntry={!showPassword}
              autoComplete="new-password"
              error={errors.confirm}
            />

            <AppButton
              title="Save Password"
              loading={loading}
              onPress={handleSubmit}
            />
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
    lineHeight: 20,
  },
});
