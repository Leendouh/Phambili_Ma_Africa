import { useEffect, useRef, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { isSignInWithEmailLink, sendSignInLinkToEmail, signInWithEmailLink } from 'firebase/auth';
import { auth } from '../firebase';
import { useAuth } from '../context/AuthContext';

const COOLDOWN_SECONDS = 30;

function sendErrorMessage(code?: string): string {
  switch (code) {
    case 'auth/operation-not-allowed':
      return 'Email-link sign-in is not switched on yet. In Firebase Console open Authentication, Sign-in method, Email/Password, and enable "Email link (passwordless sign-in)".';
    case 'auth/unauthorized-continue-uri':
      return 'This website address is not authorised in Firebase. Add it under Authentication, Settings, Authorised domains.';
    case 'auth/too-many-requests':
    case 'auth/quota-exceeded':
      return 'Too many emails were requested. Please wait a few minutes and try again.';
    case 'auth/network-request-failed':
      return 'No internet connection. Please check your network and try again.';
    default:
      return 'We could not send the verification email. Please try again.';
  }
}

function verifyErrorMessage(code?: string): string {
  switch (code) {
    case 'auth/invalid-action-code':
    case 'auth/expired-action-code':
      return 'This link has expired or was already used. Please send a new one.';
    case 'auth/invalid-email':
      return 'This link was sent to a different email address.';
    case 'auth/network-request-failed':
      return 'No internet connection. Please check your network and try again.';
    default:
      return 'We could not verify this link. Please send a new one.';
  }
}

export default function Verify() {
  const { currentUser, userProfile, loading, mfaVerified, markMfaVerified, logout } = useAuth();
  const navigate = useNavigate();
  const [sent, setSent] = useState(false);
  const [sending, setSending] = useState(false);
  const [verifying, setVerifying] = useState(false);
  const [error, setError] = useState('');
  const [cooldown, setCooldown] = useState(0);
  const handledLink = useRef(false);

  const cameFromEmailLink = isSignInWithEmailLink(auth, window.location.href);

  useEffect(() => {
    if (loading) return;
    if (!currentUser) {
      if (!cameFromEmailLink) navigate('/login', { replace: true });
      return;
    }
    if (userProfile?.role !== 'staff') navigate('/login', { replace: true });
    else if (mfaVerified) navigate('/dashboard', { replace: true });
  }, [loading, currentUser, userProfile, mfaVerified, cameFromEmailLink, navigate]);

  useEffect(() => {
    if (cooldown <= 0) return;
    const timer = setTimeout(() => setCooldown((c) => c - 1), 1000);
    return () => clearTimeout(timer);
  }, [cooldown]);

  useEffect(() => {
    if (loading || !cameFromEmailLink || handledLink.current) return;
    if (!currentUser || userProfile?.role !== 'staff') return;
    handledLink.current = true;

    const completeVerification = async () => {
      setVerifying(true);
      setError('');
      try {
        const email = currentUser.email ?? '';
        const result = await signInWithEmailLink(auth, email, window.location.href);
        markMfaVerified(result.user.uid);
        window.history.replaceState({}, '', '/verify');
        navigate('/dashboard', { replace: true });
      } catch (err) {
        window.history.replaceState({}, '', '/verify');
        setError(verifyErrorMessage((err as { code?: string }).code));
        setVerifying(false);
      }
    };

    completeVerification();
  }, [loading, cameFromEmailLink, currentUser, userProfile, markMfaVerified, navigate]);

  const sendLink = async () => {
    if (!currentUser?.email) return;
    setError('');
    setSending(true);
    try {
      await sendSignInLinkToEmail(auth, currentUser.email, {
        url: `${window.location.origin}/verify`,
        handleCodeInApp: true,
      });
      setSent(true);
      setCooldown(COOLDOWN_SECONDS);
    } catch (err) {
      setError(sendErrorMessage((err as { code?: string }).code));
    } finally {
      setSending(false);
    }
  };

  const cancel = async () => {
    try {
      await logout();
    } finally {
      navigate('/login', { replace: true });
    }
  };

  if (loading || verifying) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gray-100">
        <div className="text-gray-600">{verifying ? 'Verifying your link...' : 'Loading...'}</div>
      </div>
    );
  }

  const openedWithoutSession = cameFromEmailLink && !currentUser;

  return (
    <div className="min-h-screen flex items-center justify-center bg-gray-100 px-4">
      <div className="max-w-md w-full bg-white rounded-lg shadow-md p-8">
        <div className="text-center mb-6">
          <h1 className="text-3xl font-bold text-brand-700">Phambili Ma Africa</h1>
          <p className="text-gray-600 mt-2">Two-step verification</p>
        </div>

        {error && (
          <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4">
            {error}
          </div>
        )}

        {openedWithoutSession ? (
          <div className="space-y-4">
            <p className="text-gray-700">
              Please open this link in the same browser where you signed in with your password.
              Sign in first, then use the link from your email again.
            </p>
            <button
              onClick={() => navigate('/login', { replace: true })}
              className="w-full bg-brand-700 text-white py-2 px-4 rounded-md hover:bg-brand-800 transition-colors"
            >
              Go to sign in
            </button>
          </div>
        ) : (
          <div className="space-y-4">
            <p className="text-gray-700">
              To protect staff data we need to confirm it is really you. We will email a one-time
              verification link to <strong>{currentUser?.email}</strong>.
            </p>

            {sent && (
              <div className="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded">
                Link sent. Check your inbox (and spam folder) and open the link in this browser.
                This page will continue automatically.
              </div>
            )}

            <button
              onClick={sendLink}
              disabled={sending || cooldown > 0}
              className="w-full bg-brand-700 text-white py-2 px-4 rounded-md hover:bg-brand-800 disabled:bg-gray-400 transition-colors"
            >
              {sending
                ? 'Sending...'
                : cooldown > 0
                  ? `Send again in ${cooldown}s`
                  : sent
                    ? 'Send a new link'
                    : 'Email me a verification link'}
            </button>
          </div>
        )}

        <button
          onClick={cancel}
          className="w-full mt-4 text-sm text-gray-500 hover:text-gray-700 underline"
        >
          Cancel and sign out
        </button>
      </div>
    </div>
  );
}
