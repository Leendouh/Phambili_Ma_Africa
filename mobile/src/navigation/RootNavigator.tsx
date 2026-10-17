import { NavigationContainer } from '@react-navigation/native';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import AuthNavigator from './AuthNavigator';
import RequestDetailScreen from '../screens/customer/RequestDetailScreen';
import RequestConfirmationScreen from '../screens/customer/RequestConfirmationScreen';
import NewRequestScreen from '../screens/customer/NewRequestScreen';
import QuotationScreen from '../screens/customer/QuotationScreen';
import JobDetailScreen from '../screens/employee/JobDetailScreen';

const Stack = createNativeStackNavigator();

export default function RootNavigator() {
  return (
    <NavigationContainer>
      <Stack.Navigator screenOptions={{ headerShown: false }}>
        <Stack.Screen name="AuthNavigator" component={AuthNavigator} />
        <Stack.Screen name="NewRequest" component={NewRequestScreen} />
        <Stack.Screen name="Quotation" component={QuotationScreen} />
        <Stack.Screen name="RequestConfirmation" component={RequestConfirmationScreen} />
        <Stack.Screen name="RequestDetail" component={RequestDetailScreen} />
        <Stack.Screen name="JobDetail" component={JobDetailScreen} />
      </Stack.Navigator>
    </NavigationContainer>
  );
}
