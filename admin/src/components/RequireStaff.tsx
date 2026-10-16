import { Navigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';

/** Only lets signed-in staff who have completed the second step through. */
export default function RequireStaff({ children }: { children: React.ReactNode }) {
  const { currentUser, userProfile, loading, mfaVerified } = useAuth();

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gray-100">
        <div className="text-gray-600">Loading...</div>
      </div>
    );
  }

  if (!currentUser || userProfile?.role !== 'staff') return <Navigate to="/login" replace />;
  if (!mfaVerified) return <Navigate to="/verify" replace />;

  return <>{children}</>;
}
