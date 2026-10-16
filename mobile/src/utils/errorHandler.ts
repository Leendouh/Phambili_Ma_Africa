/**
 * Firebase error code to user-friendly message mapping
 */

export const getFirebaseErrorMessage = (error: any): string => {
  const code = error?.code || error?.message;

  // Auth errors
  if (code === 'auth/invalid-credential' || code === 'auth/invalid-email') {
    return 'Invalid email or password';
  }
  if (code === 'auth/user-not-found') {
    return 'No account found with this email';
  }
  if (code === 'auth/wrong-password') {
    return 'Incorrect password';
  }
  if (code === 'auth/email-already-in-use') {
    return 'An account with this email already exists';
  }
  if (code === 'auth/weak-password') {
    return 'Password is too weak. Please use a stronger password';
  }
  if (code === 'auth/network-request-failed') {
    return 'No internet connection. Please check your network and try again';
  }
  if (code === 'auth/too-many-requests') {
    return 'Too many attempts. Please wait a few minutes and try again';
  }
  if (code === 'auth/user-disabled') {
    return 'This account has been disabled';
  }
  if (code === 'auth/operation-not-allowed') {
    return 'This operation is not allowed';
  }

  // Firestore errors
  if (code === 'permission-denied') {
    return 'You do not have permission to perform this action';
  }
  if (code === 'not-found') {
    return 'The requested data was not found';
  }
  if (code === 'already-exists') {
    return 'This data already exists';
  }
  if (code === 'unavailable') {
    return 'Service is currently unavailable. Please try again later';
  }
  if (code === 'deadline-exceeded') {
    return 'Request timed out. Please try again';
  }
  if (code === 'unauthenticated') {
    return 'Your session has expired. Please sign in again';
  }
  if (code === 'resource-exhausted') {
    return 'The service is busy right now. Please try again shortly';
  }
  if (code === 'failed-precondition') {
    return 'This action cannot be completed right now';
  }
  if (typeof code === 'string' && code.toLowerCase().includes('client is offline')) {
    return 'No internet connection. Please check your network and try again';
  }

  // Generic errors
  if (error?.message) {
    return error.message;
  }

  return 'Something went wrong. Please try again';
};
