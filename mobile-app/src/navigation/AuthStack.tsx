import { NativeStackScreenProps, createNativeStackNavigator } from '@react-navigation/native-stack';
import { View, Text, StyleSheet, TouchableOpacity, ScrollView, Dimensions, TextInput } from 'react-native';
import { RouteProp } from '@react-navigation/native';
import { useEffect, useState, useRef } from 'react';
import { Platform } from 'react-native';
import { AuthStackParamList } from './types';
import { useAuthStore } from '../store/authStore';
import AsyncStorage from '@react-native-async-storage/async-storage';
import * as SecureStore from 'expo-secure-store';
import { useTheme } from '../theme';
import { Ionicons } from '@expo/vector-icons';
import apiClient from '../api/client';

const Stack = createNativeStackNavigator<AuthStackParamList>();

const getStorageItem = async (key: string): Promise<string | null> => {
  if (Platform.OS === 'web') {
    return await AsyncStorage.getItem(key);
  }
  return await SecureStore.getItemAsync(key);
};

const setStorageItem = async (key: string, value: string): Promise<void> => {
  if (Platform.OS === 'web') {
    await AsyncStorage.setItem(key, value);
  } else {
    await SecureStore.setItemAsync(key, value);
  }
};

function SplashScreen({ navigation }: NativeStackScreenProps<AuthStackParamList, 'Splash'>) {
  const { isAuthenticated, isLoading } = useAuthStore();

  useEffect(() => {
    const checkOnboarding = async () => {
      const hasSeenOnboarding = await getStorageItem('hasSeenOnboarding');

      if (isLoading) {
        return;
      }

      if (isAuthenticated) {
        return;
      }

      if (!hasSeenOnboarding) {
        navigation.replace('Onboarding');
      } else {
        navigation.replace('Login');
      }
    };

    checkOnboarding();
  }, [isAuthenticated, isLoading, navigation]);

  return (
    <View style={{ flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: '#0066cc' }}>
      <Text style={{ color: '#fff', fontSize: 24, fontWeight: 'bold' }}>Phambili Ma-Africa</Text>
    </View>
  );
}

function OnboardingScreen({ navigation }: NativeStackScreenProps<AuthStackParamList, 'Onboarding'>) {
  const { colors } = useTheme();
  const [currentIndex, setCurrentIndex] = useState(0);
  const scrollViewRef = useRef<ScrollView>(null);
  const { width } = Dimensions.get('window');

  const slides = [
    {
      title: 'Book in 60 seconds',
      description: 'Professional cleaning services at your fingertips. Quick, easy, and reliable bookings.',
      icon: 'time-outline',
    },
    {
      title: 'Track your team',
      description: 'Real-time updates on your booking status. Know when your team is on the way.',
      icon: 'location-outline',
    },
    {
      title: 'Get instant quotes',
      description: 'Receive accurate quotes for your cleaning needs within minutes.',
      icon: 'document-text-outline',
    },
  ];

  const handleNext = () => {
    if (currentIndex < slides.length - 1) {
      const nextIndex = currentIndex + 1;
      setCurrentIndex(nextIndex);
      scrollViewRef.current?.scrollTo({ x: width * nextIndex, animated: true });
    } else {
      handleGetStarted();
    }
  };

  const handleGetStarted = async () => {
    await setStorageItem('hasSeenOnboarding', 'true');
    navigation.replace('Login');
  };

  const handleSkip = () => {
    setStorageItem('hasSeenOnboarding', 'true').then(() => {
      navigation.replace('Login');
    });
  };

  return (
    <View style={[styles.container, { backgroundColor: colors.background }]}>
      <TouchableOpacity style={styles.skipButton} onPress={handleSkip}>
        <Text style={[styles.skipText, { color: colors.primary }]}>Skip</Text>
      </TouchableOpacity>

      <ScrollView
        ref={scrollViewRef}
        horizontal
        pagingEnabled
        showsHorizontalScrollIndicator={false}
        onMomentumScrollEnd={(e) => {
          const index = Math.round(e.nativeEvent.contentOffset.x / width);
          setCurrentIndex(index);
        }}
      >
        {slides.map((slide, index) => (
          <View key={index} style={[styles.slide, { width }]}>
            <View style={styles.iconContainer}>
              <Ionicons name={slide.icon as any} size={120} color={colors.primary} />
            </View>
            <Text style={[styles.title, { color: colors.text }]}>{slide.title}</Text>
            <Text style={[styles.description, { color: colors.textLight }]}>{slide.description}</Text>
          </View>
        ))}
      </ScrollView>

      <View style={styles.pagination}>
        {slides.map((_, index) => (
          <View
            key={index}
            style={[
              styles.dot,
              { backgroundColor: index === currentIndex ? colors.primary : colors.border },
            ]}
          />
        ))}
      </View>

      <TouchableOpacity
        style={[styles.nextButton, { backgroundColor: colors.primary }]}
        onPress={handleNext}
      >
        <Text style={styles.nextButtonText}>
          {currentIndex === slides.length - 1 ? 'Get Started' : 'Next'}
        </Text>
      </TouchableOpacity>
    </View>
  );
}

function LoginScreen({ navigation }: NativeStackScreenProps<AuthStackParamList, 'Login'>) {
  const { colors } = useTheme();
  const { setAuth } = useAuthStore();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState('');

  const handleLogin = async () => {
    setError('');

    if (!email || !password) {
      setError('Please fill in all fields');
      return;
    }

    setIsLoading(true);
    try {
      const response = await apiClient.post('/auth.php', {
        action: 'login',
        email,
        password,
        remember: true,
      });

      const payload = response.data;

      if (!payload?.success || !payload?.user) {
        throw new Error(payload?.message || 'Login failed');
      }

      const userRole = (payload.user.role || payload.user.user_type || 'customer').toLowerCase();
      if (userRole === 'admin' || userRole === 'main_admin' || userRole === 'super_admin') {
        setError('Admin accounts are not allowed to sign in on the mobile app. Please use the website admin portal.');
        return;
      }

      await setAuth(
        {
          id: Number(payload.user.id),
          name: payload.user.name || payload.user.full_name || 'Customer',
          email: payload.user.email,
          role: 'customer',
        },
        payload.token || ''
      );
    } catch (err: any) {
      setError(err?.response?.data?.message || err?.message || 'Login failed. Please check your credentials.');
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <View style={[styles.loginContainer, { backgroundColor: colors.background }]}>
      <View style={styles.loginContent}>
        <Text style={[styles.loginTitle, { color: colors.text }]}>Welcome back</Text>
        <Text style={[styles.loginSubtitle, { color: colors.textLight }]}>Sign in to continue</Text>

        <View style={styles.form}>
          <View style={styles.inputContainer}>
            <Text style={[styles.inputLabel, { color: colors.text }]}>Email</Text>
            <TextInput
              style={[styles.input, { borderColor: colors.border, color: colors.text }]}
              placeholder="Enter your email"
              placeholderTextColor={colors.textMuted}
              value={email}
              onChangeText={setEmail}
              autoCapitalize="none"
              keyboardType="email-address"
            />
          </View>

          <View style={styles.inputContainer}>
            <Text style={[styles.inputLabel, { color: colors.text }]}>Password</Text>
            <TextInput
              style={[styles.input, { borderColor: colors.border, color: colors.text }]}
              placeholder="Enter your password"
              placeholderTextColor={colors.textMuted}
              value={password}
              onChangeText={setPassword}
              secureTextEntry
            />
          </View>

          {error ? <Text style={styles.errorText}>{error}</Text> : null}

          <TouchableOpacity
            style={[styles.loginButton, { backgroundColor: colors.primary }]}
            onPress={handleLogin}
            disabled={isLoading}
          >
            <Text style={styles.loginButtonText}>
              {isLoading ? 'Signing in...' : 'Sign In'}
            </Text>
          </TouchableOpacity>

          <TouchableOpacity onPress={() => navigation.navigate('ForgotPassword')}>
            <Text style={[styles.forgotText, { color: colors.primary }]}>Forgot password?</Text>
          </TouchableOpacity>

          <View style={styles.registerContainer}>
            <Text style={[styles.registerText, { color: colors.textLight }]}>Don't have an account? </Text>
            <TouchableOpacity onPress={() => navigation.navigate('Register')}>
              <Text style={[styles.registerLink, { color: colors.primary }]}>Sign up</Text>
            </TouchableOpacity>
          </View>
        </View>
      </View>
    </View>
  );
}

function RegisterScreen({ navigation }: NativeStackScreenProps<AuthStackParamList, 'Register'>) {
  const { colors } = useTheme();
  const { setAuth } = useAuthStore();
  const [fullName, setFullName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState('');

  const handleRegister = async () => {
    setError('');

    if (!fullName.trim() || !email.trim() || !password || !confirmPassword) {
      setError('Please fill in all fields');
      return;
    }

    if (fullName.trim().length < 2) {
      setError('Full name must be at least 2 characters long');
      return;
    }

    if (!/^[a-zA-Z\s\-']+$/.test(fullName.trim())) {
      setError('Full name can only contain letters, spaces, hyphens, and apostrophes');
      return;
    }

    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.trim())) {
      setError('Please enter a valid email address');
      return;
    }

    if (password.length < 8) {
      setError('Password must be at least 8 characters long');
      return;
    }

    if (password !== confirmPassword) {
      setError('Passwords do not match');
      return;
    }

    setIsLoading(true);
    try {
      const response = await apiClient.post('/auth.php', {
        action: 'signup',
        fullName: fullName.trim(),
        email: email.trim(),
        password,
        confirmPassword,
      });

      const payload = response.data;

      if (!payload?.success || !payload?.user) {
        const backendError = payload?.errors ? Object.values(payload.errors)[0] : payload?.message;
        throw new Error(backendError || 'Registration failed');
      }

      const userRole = (payload.user.role || payload.user.user_type || 'customer').toLowerCase();
      if (userRole === 'admin' || userRole === 'main_admin' || userRole === 'super_admin') {
        setError('Admin accounts are not allowed on the mobile app. Please sign in via the website dashboard.');
        return;
      }

      await setAuth(
        {
          id: Number(payload.user.id),
          name: payload.user.name || payload.user.full_name || fullName.trim(),
          email: payload.user.email,
          role: 'customer',
        },
        payload.token || ''
      );
    } catch (err: any) {
      setError(err?.response?.data?.message || err?.message || 'Registration failed. Please try again.');
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <View style={[styles.loginContainer, { backgroundColor: colors.background }]}>
      <View style={styles.loginContent}>
        <Text style={[styles.loginTitle, { color: colors.text }]}>Create account</Text>
        <Text style={[styles.loginSubtitle, { color: colors.textLight }]}>Join Phambili Ma-Africa</Text>

        <View style={styles.form}>
          <View style={styles.inputContainer}>
            <Text style={[styles.inputLabel, { color: colors.text }]}>Full name</Text>
            <TextInput
              style={[styles.input, { borderColor: colors.border, color: colors.text }]}
              placeholder="Enter your full name"
              placeholderTextColor={colors.textMuted}
              value={fullName}
              onChangeText={setFullName}
              autoCapitalize="words"
            />
          </View>

          <View style={styles.inputContainer}>
            <Text style={[styles.inputLabel, { color: colors.text }]}>Email</Text>
            <TextInput
              style={[styles.input, { borderColor: colors.border, color: colors.text }]}
              placeholder="Enter your email"
              placeholderTextColor={colors.textMuted}
              value={email}
              onChangeText={setEmail}
              autoCapitalize="none"
              keyboardType="email-address"
            />
          </View>

          <View style={styles.inputContainer}>
            <Text style={[styles.inputLabel, { color: colors.text }]}>Password</Text>
            <TextInput
              style={[styles.input, { borderColor: colors.border, color: colors.text }]}
              placeholder="Create a password"
              placeholderTextColor={colors.textMuted}
              value={password}
              onChangeText={setPassword}
              secureTextEntry
            />
          </View>

          <View style={styles.inputContainer}>
            <Text style={[styles.inputLabel, { color: colors.text }]}>Confirm password</Text>
            <TextInput
              style={[styles.input, { borderColor: colors.border, color: colors.text }]}
              placeholder="Re-enter your password"
              placeholderTextColor={colors.textMuted}
              value={confirmPassword}
              onChangeText={setConfirmPassword}
              secureTextEntry
            />
          </View>

          {error ? <Text style={styles.errorText}>{error}</Text> : null}

          <TouchableOpacity
            style={[styles.loginButton, { backgroundColor: colors.primary }]}
            onPress={handleRegister}
            disabled={isLoading}
          >
            <Text style={styles.loginButtonText}>
              {isLoading ? 'Creating account...' : 'Create account'}
            </Text>
          </TouchableOpacity>

          <View style={styles.registerContainer}>
            <Text style={[styles.registerText, { color: colors.textLight }]}>Already have an account? </Text>
            <TouchableOpacity onPress={() => navigation.navigate('Login')}>
              <Text style={[styles.registerLink, { color: colors.primary }]}>Sign in</Text>
            </TouchableOpacity>
          </View>
        </View>
      </View>
    </View>
  );
}

function ForgotPasswordScreen({ navigation }: NativeStackScreenProps<AuthStackParamList, 'ForgotPassword'>) {
  const { colors } = useTheme();
  const [email, setEmail] = useState('');
  const [busy, setBusy] = useState(false);
  const [message, setMessage] = useState('');

  const handleSendCode = async () => {
    const trimmedEmail = email.trim();
    if (!trimmedEmail || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(trimmedEmail)) {
      setMessage('Please enter a valid email address.');
      return;
    }

    setBusy(true);
    setMessage('');

    try {
      const response = await apiClient.post('/otp_actions.php?action=send-otp', {
        email: trimmedEmail,
        otp_type: 'password_reset',
      });

      if (!response.data?.success) {
        throw new Error(response.data?.message || 'Could not send OTP code.');
      }

      navigation.navigate('OTP', { email: trimmedEmail });
    } catch (error: any) {
      setMessage(error?.response?.data?.message || error?.message || 'Unable to send reset code.');
    } finally {
      setBusy(false);
    }
  };

  return (
    <View style={[styles.loginContainer, { backgroundColor: colors.background }]}> 
      <View style={styles.loginContent}>
        <TouchableOpacity onPress={() => navigation.goBack()} style={{ marginBottom: 18, alignSelf: 'flex-start' }}>
          <View style={{ flexDirection: 'row', alignItems: 'center' }}>
            <Ionicons name="arrow-back" size={18} color={colors.primary} />
            <Text style={[styles.registerLink, { color: colors.primary, marginLeft: 8 }]}>Back</Text>
          </View>
        </TouchableOpacity>

        <Text style={[styles.loginTitle, { color: colors.text }]}>Reset password</Text>
        <Text style={[styles.loginSubtitle, { color: colors.textLight }]}>We’ll send a one-time code to your email.</Text>

        <View style={styles.form}>
          <View style={styles.inputContainer}>
            <Text style={[styles.inputLabel, { color: colors.text }]}>Email address</Text>
            <TextInput
              style={[styles.input, { borderColor: colors.border, color: colors.text, backgroundColor: colors.surface }]}
              placeholder="Enter your account email"
              placeholderTextColor={colors.textMuted}
              value={email}
              onChangeText={setEmail}
              autoCapitalize="none"
              keyboardType="email-address"
            />
          </View>

          {message ? <Text style={styles.errorText}>{message}</Text> : null}

          <TouchableOpacity
            style={[styles.loginButton, { backgroundColor: colors.primary }]}
            onPress={handleSendCode}
            disabled={busy}
          >
            <Text style={styles.loginButtonText}>{busy ? 'Sending...' : 'Send code'}</Text>
          </TouchableOpacity>
        </View>
      </View>
    </View>
  );
}

function OTPScreen({ navigation, route }: NativeStackScreenProps<AuthStackParamList, 'OTP'>) {
  const { colors } = useTheme();
  const { email } = route.params;
  const [otp, setOtp] = useState('');
  const [newPassword, setNewPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [message, setMessage] = useState('');
  const [busy, setBusy] = useState(false);
  const [codeVerified, setCodeVerified] = useState(false);
  const [resetDone, setResetDone] = useState(false);

  const handleVerifyCode = async () => {
    const trimmedOtp = otp.trim();
    if (!trimmedOtp) {
      setMessage('Please enter the verification code.');
      return;
    }

    setBusy(true);
    setMessage('');

    try {
      const response = await apiClient.post('/otp_actions.php?action=verify-otp', {
        email,
        otp_code: trimmedOtp,
        otp_type: 'password_reset',
      });

      if (!response.data?.success) {
        throw new Error(response.data?.message || 'Invalid verification code.');
      }

      setCodeVerified(true);
      setMessage('Code verified. Please set a new password.');
    } catch (error: any) {
      setMessage(error?.response?.data?.message || error?.message || 'Verification failed.');
    } finally {
      setBusy(false);
    }
  };

  const handleResetPassword = async () => {
    if (!newPassword || !confirmPassword) {
      setMessage('Please complete both password fields.');
      return;
    }

    if (newPassword.length < 8) {
      setMessage('Password must be at least 8 characters long.');
      return;
    }

    if (newPassword !== confirmPassword) {
      setMessage('Passwords do not match.');
      return;
    }

    setBusy(true);
    setMessage('');

    try {
      const response = await apiClient.post('/otp_actions.php?action=reset-password', {
        email,
        new_password: newPassword,
      });

      if (!response.data?.success) {
        throw new Error(response.data?.message || 'Password reset failed.');
      }

      setResetDone(true);
      setMessage('Password updated successfully.');
    } catch (error: any) {
      setMessage(error?.response?.data?.message || error?.message || 'Unable to reset password.');
    } finally {
      setBusy(false);
    }
  };

  return (
    <View style={[styles.loginContainer, { backgroundColor: colors.background }]}> 
      <View style={styles.loginContent}>
        <TouchableOpacity onPress={() => navigation.goBack()} style={{ marginBottom: 18, alignSelf: 'flex-start' }}>
          <View style={{ flexDirection: 'row', alignItems: 'center' }}>
            <Ionicons name="arrow-back" size={18} color={colors.primary} />
            <Text style={[styles.registerLink, { color: colors.primary, marginLeft: 8 }]}>Back</Text>
          </View>
        </TouchableOpacity>

        <Text style={[styles.loginTitle, { color: colors.text }]}>Verify code</Text>
        <Text style={[styles.loginSubtitle, { color: colors.textLight }]}>We sent a 6-digit code to {email}</Text>

        <View style={styles.form}>
          {!codeVerified && !resetDone ? (
            <>
              <View style={styles.inputContainer}>
                <Text style={[styles.inputLabel, { color: colors.text }]}>OTP code</Text>
                <TextInput
                  style={[styles.input, { borderColor: colors.border, color: colors.text, backgroundColor: colors.surface }]}
                  placeholder="Enter the code"
                  placeholderTextColor={colors.textMuted}
                  value={otp}
                  onChangeText={setOtp}
                  autoCapitalize="none"
                  keyboardType="number-pad"
                />
              </View>

              {message ? <Text style={styles.errorText}>{message}</Text> : null}

              <TouchableOpacity
                style={[styles.loginButton, { backgroundColor: colors.primary }]}
                onPress={handleVerifyCode}
                disabled={busy}
              >
                <Text style={styles.loginButtonText}>{busy ? 'Verifying...' : 'Verify code'}</Text>
              </TouchableOpacity>
            </>
          ) : null}

          {codeVerified && !resetDone ? (
            <>
              <View style={styles.inputContainer}>
                <Text style={[styles.inputLabel, { color: colors.text }]}>New password</Text>
                <TextInput
                  style={[styles.input, { borderColor: colors.border, color: colors.text, backgroundColor: colors.surface }]}
                  placeholder="Create a new password"
                  placeholderTextColor={colors.textMuted}
                  value={newPassword}
                  onChangeText={setNewPassword}
                  secureTextEntry
                />
              </View>

              <View style={styles.inputContainer}>
                <Text style={[styles.inputLabel, { color: colors.text }]}>Confirm password</Text>
                <TextInput
                  style={[styles.input, { borderColor: colors.border, color: colors.text, backgroundColor: colors.surface }]}
                  placeholder="Confirm your password"
                  placeholderTextColor={colors.textMuted}
                  value={confirmPassword}
                  onChangeText={setConfirmPassword}
                  secureTextEntry
                />
              </View>

              {message ? <Text style={styles.errorText}>{message}</Text> : null}

              <TouchableOpacity
                style={[styles.loginButton, { backgroundColor: colors.primary }]}
                onPress={handleResetPassword}
                disabled={busy}
              >
                <Text style={styles.loginButtonText}>{busy ? 'Updating...' : 'Update password'}</Text>
              </TouchableOpacity>
            </>
          ) : null}

          {resetDone ? (
            <>
              <Text style={[styles.errorText, { color: colors.primary, textAlign: 'center' }]}>{message}</Text>
              <TouchableOpacity
                style={[styles.loginButton, { backgroundColor: colors.primary }]}
                onPress={() => navigation.navigate('Login')}
              >
                <Text style={styles.loginButtonText}>Back to login</Text>
              </TouchableOpacity>
            </>
          ) : null}
        </View>
      </View>
    </View>
  );
}

export default function AuthStack() {
  return (
    <Stack.Navigator screenOptions={{ headerShown: false }}>
      <Stack.Screen name="Splash" component={SplashScreen} />
      <Stack.Screen name="Onboarding" component={OnboardingScreen} />
      <Stack.Screen name="Login" component={LoginScreen} />
      <Stack.Screen name="Register" component={RegisterScreen} />
      <Stack.Screen name="ForgotPassword" component={ForgotPasswordScreen} />
      <Stack.Screen name="OTP" component={OTPScreen} />
    </Stack.Navigator>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
  skipButton: {
    position: 'absolute',
    top: 50,
    right: 20,
    padding: 10,
  },
  skipText: {
    fontSize: 16,
    fontWeight: '600',
  },
  slide: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    paddingHorizontal: 20,
  },
  iconContainer: {
    marginBottom: 30,
  },
  title: {
    fontSize: 28,
    fontWeight: 'bold',
    textAlign: 'center',
    marginBottom: 15,
  },
  description: {
    fontSize: 16,
    textAlign: 'center',
    paddingHorizontal: 30,
    lineHeight: 24,
  },
  pagination: {
    flexDirection: 'row',
    justifyContent: 'center',
    marginBottom: 30,
  },
  dot: {
    width: 10,
    height: 10,
    borderRadius: 5,
    marginHorizontal: 5,
  },
  nextButton: {
    marginHorizontal: 20,
    marginBottom: 40,
    paddingVertical: 16,
    borderRadius: 25,
    alignItems: 'center',
  },
  nextButtonText: {
    color: '#fff',
    fontSize: 18,
    fontWeight: 'bold',
  },
  loginContainer: {
    flex: 1,
    padding: 20,
  },
  loginContent: {
    flex: 1,
    justifyContent: 'center',
  },
  loginTitle: {
    fontSize: 32,
    fontWeight: 'bold',
    marginBottom: 8,
  },
  loginSubtitle: {
    fontSize: 16,
    marginBottom: 32,
  },
  form: {
    width: '100%',
  },
  inputContainer: {
    marginBottom: 20,
  },
  inputLabel: {
    fontSize: 14,
    fontWeight: '600',
    marginBottom: 8,
  },
  input: {
    borderWidth: 1,
    borderRadius: 8,
    paddingHorizontal: 16,
    paddingVertical: 12,
    fontSize: 16,
  },
  errorText: {
    color: '#dc3545',
    fontSize: 14,
    marginBottom: 16,
  },
  loginButton: {
    paddingVertical: 16,
    borderRadius: 25,
    alignItems: 'center',
    marginTop: 8,
  },
  loginButtonText: {
    color: '#fff',
    fontSize: 18,
    fontWeight: 'bold',
  },
  forgotText: {
    fontSize: 14,
    textAlign: 'center',
    marginTop: 16,
    textDecorationLine: 'underline',
  },
  registerContainer: {
    flexDirection: 'row',
    justifyContent: 'center',
    marginTop: 24,
  },
  registerText: {
    fontSize: 14,
  },
  registerLink: {
    fontSize: 14,
    fontWeight: '600',
  },
});
