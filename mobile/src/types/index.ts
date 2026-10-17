/**
 * TypeScript type definitions for the application
 */

export type UserRole = 'customer' | 'employee' | 'staff';

export type RequestStatus = 'Pending' | 'Approved' | 'Rejected' | 'In Progress' | 'Completed';

export interface User {
  uid: string;
  role: UserRole;
  firstName: string;
  lastName: string;
  email: string;
  phone?: string;
  address?: string;
  isAvailable?: boolean;
<<<<<<< HEAD
  firstLogin?: boolean;
=======
>>>>>>> 6f19b56de913460717b19fe32251bb23c912f548
  dateRegistered?: string;
}

export interface Service {
  id: string;
  name: string;
  description: string;
  price: number;
  isActive: boolean;
<<<<<<< HEAD
=======
  category?: string;
  duration?: number;
  imageUrl?: string;
>>>>>>> 6f19b56de913460717b19fe32251bb23c912f548
}

export interface ServiceRequest {
  id: string;
  refNumber: string;
  customerId: string;
  customerName?: string;
  customerPhone?: string;
<<<<<<< HEAD
  serviceId: string;
  serviceName: string;
=======
  customerEmail?: string;
  serviceId: string;
  serviceName: string;
  propertyType?: string;
  cleaningFrequency?: string;
  specialRequests?: string;
  duration?: number | null;
>>>>>>> 6f19b56de913460717b19fe32251bb23c912f548
  employeeId?: string;
  employeeName?: string;
  staffId?: string;
  requestedDate: string;
  requestedTime: string;
  siteAddress: string;
  status: RequestStatus;
  hasFeedback: boolean;
  dateCreated: string;
}

export interface Feedback {
  requestId: string;
  customerId: string;
  rating: number;
  comment?: string;
  dateSubmitted: string;
}

export interface Notification {
  id: string;
  recipientId: string;
  requestId: string;
  message: string;
  isRead: boolean;
  dateCreated: string;
}

export interface RegisterFormData {
  firstName: string;
  lastName: string;
  email: string;
  phone: string;
  password: string;
  address?: string;
}

export interface LoginFormData {
  email: string;
  password: string;
}

export interface NewRequestFormData {
  serviceId: string;
  requestedDate: string;
  requestedTime: string;
  siteAddress: string;
}

export interface FeedbackFormData {
  rating: number;
  comment?: string;
}
