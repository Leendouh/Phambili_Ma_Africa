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
  dateRegistered?: string;
}

export interface Service {
  id: string;
  name: string;
  description: string;
  price: number;
  isActive: boolean;
}

export interface ServiceRequest {
  id: string;
  refNumber: string;
  customerId: string;
  serviceId: string;
  serviceName: string;
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
