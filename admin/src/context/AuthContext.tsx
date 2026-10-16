import { createContext, useContext, useEffect, useState } from 'react';
import {
  type User as FirebaseUser,
  signInWithEmailAndPassword,
  signOut,
  onAuthStateChanged,
} from 'firebase/auth';
import { doc, getDoc } from 'firebase/firestore';
import { auth, db } from '../firebase';
import { MFA_STORAGE_KEY, MFA_TTL_MS, REQUIRE_EMAIL_2FA } from '../config';
import type { User } from '../types';

interface AuthContextType {
  currentUser: FirebaseUser | null;
  userProfile: User | null;
  loading: boolean;
  /** True once the staff member has completed the email second step (always true when 2FA is switched off). */
  mfaVerified: boolean;
  login: (email: string, password: string) => Promise<void>;
  logout: () => Promise<void>;
  markMfaVerified: (uid: string) => void;
}

interface MfaRecord {
  uid: string;
  expires: number;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
};

const readMfa = (): MfaRecord | null => {
  try {
    const raw = localStorage.getItem(MFA_STORAGE_KEY);
    return raw ? (JSON.parse(raw) as MfaRecord) : null;
  } catch {
    return null;
  }
};

const fetchUserProfile = async (uid: string): Promise<User | null> => {
  try {
    const userDoc = await getDoc(doc(db, 'users', uid));
    if (!userDoc.exists()) return null;
    const data = userDoc.data();
    return {
      uid,
      role: data.role,
      firstName: data.firstName,
      lastName: data.lastName,
      email: data.email,
      phone: data.phone,
      address: data.address,
      isAvailable: data.isAvailable,
      dateRegistered: data.dateRegistered,
    };
  } catch (error) {
    console.error('Error fetching user profile:', error);
    return null;
  }
};

export const AuthProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const [currentUser, setCurrentUser] = useState<FirebaseUser | null>(null);
  const [userProfile, setUserProfile] = useState<User | null>(null);
  const [loading, setLoading] = useState(true);
  const [mfa, setMfa] = useState<MfaRecord | null>(readMfa);

  useEffect(() => {
    const unsubscribe = onAuthStateChanged(auth, async (user) => {
      const profile = user ? await fetchUserProfile(user.uid) : null;
      setCurrentUser(user);
      setUserProfile(profile);
      setLoading(false);
    });

    return unsubscribe;
  }, []);

  useEffect(() => {
    const onStorage = (event: StorageEvent) => {
      if (event.key === MFA_STORAGE_KEY) setMfa(readMfa());
    };
    window.addEventListener('storage', onStorage);
    return () => window.removeEventListener('storage', onStorage);
  }, []);

  const mfaVerified =
    !REQUIRE_EMAIL_2FA ||
    (currentUser !== null && mfa !== null && mfa.uid === currentUser.uid && Date.now() < mfa.expires);

  const markMfaVerified = (uid: string) => {
    const record = { uid, expires: Date.now() + MFA_TTL_MS };
    localStorage.setItem(MFA_STORAGE_KEY, JSON.stringify(record));
    setMfa(record);
  };

  const login = async (email: string, password: string) => {
    try {
      await signInWithEmailAndPassword(auth, email, password);
    } catch (error) {
      throw new Error(loginErrorMessage((error as { code?: string }).code));
    }
  };

  const logout = async () => {
    localStorage.removeItem(MFA_STORAGE_KEY);
    setMfa(null);
    try {
      await signOut(auth);
    } catch {
      throw new Error('Logout failed. Please try again.');
    }
  };

  const value = {
    currentUser,
    userProfile,
    loading,
    mfaVerified,
    login,
    logout,
    markMfaVerified,
  };

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
};

function loginErrorMessage(code?: string): string {
  switch (code) {
    case 'auth/invalid-credential':
    case 'auth/wrong-password':
    case 'auth/user-not-found':
    case 'auth/invalid-email':
      return 'Wrong email or password.';
    case 'auth/too-many-requests':
      return 'Too many attempts. Please wait a few minutes and try again.';
    case 'auth/user-disabled':
      return 'This account has been disabled.';
    case 'auth/network-request-failed':
      return 'No internet connection. Please check your network and try again.';
    default:
      return 'Login failed. Please try again.';
  }
}
