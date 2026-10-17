<<<<<<< HEAD
import React from 'react';
=======
import React, { useEffect, useState } from 'react';
>>>>>>> 6f19b56de913460717b19fe32251bb23c912f548
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { useAuth } from '../context/AuthContext';
import LoginScreen from '../screens/auth/LoginScreen';
import RegisterScreen from '../screens/auth/RegisterScreen';
<<<<<<< HEAD
import ForcePasswordScreen from '../screens/auth/ForcePasswordScreen';
=======
import SplashScreen from '../screens/auth/SplashScreen';
>>>>>>> 6f19b56de913460717b19fe32251bb23c912f548
import CustomerTabs from './CustomerTabs';
import EmployeeTabs from './EmployeeTabs';

const Stack = createNativeStackNavigator();

export default function AuthNavigator() {
  const { userProfile, loading } = useAuth();
<<<<<<< HEAD

  if (loading) {
    return null; // Or a loading screen
=======
  const [splashDone, setSplashDone] = useState(false);

  useEffect(() => {
    const timer = setTimeout(() => setSplashDone(true), 2200);
    return () => clearTimeout(timer);
  }, []);

  if (loading || !splashDone) {
    return <SplashScreen />;
>>>>>>> 6f19b56de913460717b19fe32251bb23c912f548
  }

  return (
    <Stack.Navigator screenOptions={{ headerShown: false }}>
      {!userProfile ? (
        <>
          <Stack.Screen name="Login" component={LoginScreen} />
          <Stack.Screen name="Register" component={RegisterScreen} />
        </>
<<<<<<< HEAD
      ) : userProfile.firstLogin ? (
        // Admin-created accounts must set their own password before
        // reaching any role tabs — clearing the flag re-renders the tree.
        <Stack.Screen name="ForcePassword" component={ForcePasswordScreen} />
=======
>>>>>>> 6f19b56de913460717b19fe32251bb23c912f548
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
