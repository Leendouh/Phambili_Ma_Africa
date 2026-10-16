import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';

export default function Login() {
  const { login, logout, currentUser, userProfile, loading, mfaVerified } = useAuth();
  const navigate = useNavigate();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [submitting, setSubmitting] = useState(false);

  React.useEffect(() => {
    if (loading || !currentUser) return;

    if (userProfile?.role === 'staff') {
      navigate(mfaVerified ? '/dashboard' : '/verify', { replace: true });
      return;
    }

    setError(
      userProfile
        ? 'Access denied. This dashboard is for staff only.'
        : 'This account has not been set up for the dashboard. Please contact an administrator.'
    );
    logout().catch(() => undefined);
  }, [loading, currentUser, userProfile, mfaVerified, navigate, logout]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    setSubmitting(true);

    try {
      await login(email, password);
    } catch (err: any) {
      setError(err.message || 'Login failed');
    } finally {
      setSubmitting(false);
    }
  };

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gray-100">
        <div className="text-gray-600">Loading...</div>
      </div>
    );
  }

  return (
    <div className="min-h-screen flex items-center justify-center bg-gray-100 px-4">
      <div className="max-w-md w-full bg-white rounded-lg shadow-md p-8">
        <div className="text-center mb-8">
          <h1 className="text-3xl font-bold text-brand-700">Phambili Ma Africa</h1>
          <p className="text-gray-600 mt-2">Admin Dashboard</p>
        </div>

        {error && (
          <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4">
            {error}
          </div>
        )}

        <form onSubmit={handleSubmit} className="space-y-6">
          <div>
            <label htmlFor="email" className="block text-sm font-medium text-gray-700 mb-2">
              Email
            </label>
            <input
              id="email"
              type="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-brand-500"
              required
            />
          </div>

          <div>
            <label htmlFor="password" className="block text-sm font-medium text-gray-700 mb-2">
              Password
            </label>
            <input
              id="password"
              type="password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-brand-500"
              required
            />
          </div>

          <button
            type="submit"
            disabled={submitting}
            className="w-full bg-brand-700 text-white py-2 px-4 rounded-md hover:bg-brand-800 disabled:bg-gray-400 transition-colors"
          >
            {submitting ? 'Logging in...' : 'Login'}
          </button>
        </form>

        <p className="text-center text-sm text-gray-500 mt-6">
          Staff access only. Please use the admin dashboard.
        </p>
      </div>
    </div>
  );
}
