/**
 * Validation utilities for form inputs
 */

export const validateEmail = (email: string): string | null => {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!email) return 'Email is required';
  if (!emailRegex.test(email)) return 'Please enter a valid email address';
  return null;
};

export const validateSAPhone = (phone: string): string | null => {
  if (!phone) return 'Phone number is required';
  // Accept formats: +27 12 345 6789, 27123456789, 0123456789
  const phoneRegex = /^(\+27|27)?0?[0-9]{9}$/;
  if (!phoneRegex.test(phone.replace(/\s/g, ''))) {
    return 'Please enter a valid South African phone number';
  }
  return null;
};

export const validatePassword = (password: string): string | null => {
  if (!password) return 'Password is required';
  if (password.length < 8) return 'Password must be at least 8 characters';
  if (!/[A-Za-z]/.test(password) || !/\d/.test(password)) {
    return 'Password must contain at least one letter and one number';
  }
  if (password.length > 64) return 'Password must be 64 characters or less';
  return null;
};

export const validateRequired = (value: string, fieldName: string): string | null => {
  if (!value || value.trim() === '') return `${fieldName} is required`;
  return null;
};

export const validateDateNotInPast = (date: string): string | null => {
  if (!date) return 'Date is required';
  const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(date.trim());
  if (!match) return 'Use the format YYYY-MM-DD, for example 2026-10-20';

  const [year, month, day] = [Number(match[1]), Number(match[2]), Number(match[3])];
  const selectedDate = new Date(year, month - 1, day);
  const isRealDate =
    selectedDate.getFullYear() === year &&
    selectedDate.getMonth() === month - 1 &&
    selectedDate.getDate() === day;
  if (!isRealDate) return 'That date does not exist. Please check the day and month';

  const today = new Date();
  today.setHours(0, 0, 0, 0);
  if (selectedDate < today) return 'Date cannot be in the past';

  const maxDate = new Date(today);
  maxDate.setFullYear(maxDate.getFullYear() + 1);
  if (selectedDate > maxDate) return 'Please choose a date within the next 12 months';
  return null;
};

export const validateTime = (time: string): string | null => {
  if (!time || time.trim() === '') return 'Time is required';
  const match = /^([01]\d|2[0-3]):([0-5]\d)$/.exec(time.trim());
  if (!match) return 'Use 24-hour format HH:MM, for example 09:30';
  const hour = Number(match[1]);
  if (hour < 7 || hour > 18) return 'Services are available between 07:00 and 18:00';
  return null;
};

export const validateAddress = (address: string): string | null => {
  const value = address?.trim() ?? '';
  if (!value) return 'Site address is required';
  if (value.length < 8) return 'Please enter a full address (street and town)';
  if (value.length > 200) return 'Address must be 200 characters or less';
  return null;
};

export const validateRating = (rating: number): string | null => {
  if (rating < 1 || rating > 5) return 'Rating must be between 1 and 5';
  return null;
};

export const validateCommentLength = (comment: string): string | null => {
  if (comment && comment.length > 500) return 'Comment must be 500 characters or less';
  return null;
};

export const validatePrice = (price: number): string | null => {
  if (price < 0) return 'Price cannot be negative';
  return null;
};
