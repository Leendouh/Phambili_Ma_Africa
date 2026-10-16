import { getFirebaseErrorMessage } from '../errorHandler';

describe('getFirebaseErrorMessage', () => {
  it.each([
    ['auth/invalid-credential', 'Invalid email or password'],
    ['auth/email-already-in-use', 'An account with this email already exists'],
    ['auth/weak-password', 'Password is too weak. Please use a stronger password'],
    ['auth/network-request-failed', 'No internet connection. Please check your network and try again'],
    ['auth/too-many-requests', 'Too many attempts. Please wait a few minutes and try again'],
    ['permission-denied', 'You do not have permission to perform this action'],
    ['unavailable', 'Service is currently unavailable. Please try again later'],
    ['deadline-exceeded', 'Request timed out. Please try again'],
    ['unauthenticated', 'Your session has expired. Please sign in again'],
  ])('maps %s to a friendly message', (code, message) => {
    expect(getFirebaseErrorMessage({ code })).toBe(message);
  });

  it('detects the Firestore offline message', () => {
    const error = new Error('Failed to get document because the client is offline.');
    expect(getFirebaseErrorMessage(error)).toMatch(/No internet connection/);
  });

  it('falls back to the error message for unknown errors', () => {
    expect(getFirebaseErrorMessage(new Error('Boom'))).toBe('Boom');
  });

  it('never throws on null or undefined', () => {
    expect(getFirebaseErrorMessage(null)).toBe('Something went wrong. Please try again');
    expect(getFirebaseErrorMessage(undefined)).toBe('Something went wrong. Please try again');
  });
});
