export type RootStackParamList = {
  Auth: undefined;
  Main: undefined;
};

export type AuthStackParamList = {
  Splash: undefined;
  Onboarding: undefined;
  Login: undefined;
  Register: undefined;
  ForgotPassword: undefined;
  OTP: { email: string };
};

export type CustomerTabsParamList = {
  Home: undefined;
  Bookings: undefined;
  Notifications: undefined;
  Services: undefined;
  Products: undefined;
  Profile: undefined;
  Cart: undefined;
  BookingForm: { serviceId?: number } | undefined;
};

export type CustomerStackParamList = {
  ServiceDetail: { id: number };
  ProductDetail: { id: number };
  BookingWizard: { step?: number };
  BookingDetail: { id: number };
  Search: undefined;
};

export type AdminTabsParamList = {
  Dashboard: undefined;
  AdminBookings: undefined;
  Content: undefined;
  AdminProfile: undefined;
};

export type AdminStackParamList = {
  AdminBookingDetail: { id: number };
  AssignTeam: { bookingId: number };
  CheckIn: { bookingId: number };
};
