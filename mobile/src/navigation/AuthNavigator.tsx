import React from 'react';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { useAuth } from '../context/AuthContext';
import LoginScreen from '../screens/auth/LoginScreen';
import RegisterScreen from '../screens/auth/RegisterScreen';
import ForcePasswordScreen from '../screens/auth/ForcePasswordScreen';
import CustomerTabs from './CustomerTabs';
import EmployeeTabs from './EmployeeTabs';

const Stack = createNativeStackNavigator();

export default function AuthNavigator() {
  const { userProfile, loading } = useAuth();

  if (loading) {
    return null; // Or a loading screen
  }

  return (
    <Stack.Navigator screenOptions={{ headerShown: false }}>
      {!userProfile ? (
        <>
          <Stack.Screen name="Login" component={LoginScreen} />
          <Stack.Screen name="Register" component={RegisterScreen} />
        </>
      ) : userProfile.firstLogin ? (
        // Admin-created accounts must set their own password before
        // reaching any role tabs — clearing the flag re-renders the tree.
        <Stack.Screen name="ForcePassword" component={ForcePasswordScreen} />
      ) : userProfile.role === 'customer' ? (
        <Stack.Screen name="CustomerTabs" component={CustomerTabs} />
      ) : userProfile.role === 'employee' ? (
        <Stack.Screen name="EmployeeTabs" component={EmployeeTabs} />
      ) : (
        // Staff users should use the admin dashboard
        <Stack.Screen name="Login" component={LoginScreen} />
      )}
    </Stack.Navigator>
  );
}
