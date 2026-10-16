import React, { useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  Alert,
  KeyboardAvoidingView,
  Platform,
  ScrollView,
  TouchableOpacity,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useAuth } from '../../context/AuthContext';
import { addDoc, collection } from 'firebase/firestore';
import { db } from '../../firebase';
import { Service, NewRequestFormData } from '../../types';
import { validateDateNotInPast, validateTime, validateAddress } from '../../utils/validators';
import { getFirebaseErrorMessage } from '../../utils/errorHandler';
import { AppButton, AppInput } from '../../components/ui';
import { lightColors as C } from '../../theme/colors';
import { fontFamily } from '../../theme/typography';
import { radii } from '../../theme/radii';
import { spacing } from '../../theme/spacing';

interface Props {
  route: any;
  navigation: any;
}

export default function NewRequestScreen({ route, navigation }: Props) {
  const insets = useSafeAreaInsets();
  const { userProfile } = useAuth();
  const service: Service = route.params?.service;
  const [formData, setFormData] = useState<NewRequestFormData>({
    serviceId: service?.id || '',
    requestedDate: '',
    requestedTime: '',
    siteAddress: userProfile?.address ?? '',
  });
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [loading, setLoading] = useState(false);

  const validateForm = () => {
    const newErrors: Record<string, string> = {};

    const dateError = validateDateNotInPast(formData.requestedDate);
    if (dateError) newErrors.requestedDate = dateError;

    const timeError = validateTime(formData.requestedTime);
    if (timeError) newErrors.requestedTime = timeError;

    const addressError = validateAddress(formData.siteAddress);
    if (addressError) newErrors.siteAddress = addressError;

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = async () => {
    if (!validateForm()) return;
    if (!userProfile) {
      Alert.alert('Error', 'User profile not loaded');
      return;
    }

    setLoading(true);
    try {
      const refNumber = 'PMA-' + Date.now().toString(36).toUpperCase();

      await addDoc(collection(db, 'requests'), {
        refNumber,
        customerId: userProfile.uid,
        customerName: `${userProfile.firstName} ${userProfile.lastName}`.trim(),
        customerPhone: userProfile.phone ?? '',
        serviceId: formData.serviceId,
        serviceName: service.name,
        employeeId: null,
        employeeName: null,
        staffId: null,
        requestedDate: formData.requestedDate,
        requestedTime: formData.requestedTime,
        siteAddress: formData.siteAddress,
        status: 'Pending',
        hasFeedback: false,
        dateCreated: new Date().toISOString(),
      });

      navigation.replace('RequestConfirmation', {
        refNumber,
        serviceName: service.name,
        requestedDate: formData.requestedDate,
        requestedTime: formData.requestedTime,
        siteAddress: formData.siteAddress,
      });
    } catch (error) {
      Alert.alert('Error', getFirebaseErrorMessage(error));
    } finally {
      setLoading(false);
    }
  };

  return (
    <KeyboardAvoidingView
      style={styles.container}
      behavior={Platform.OS === 'ios' ? 'padding' : undefined}
    >
      <ScrollView
        contentContainerStyle={[styles.scrollContent, { paddingTop: insets.top + spacing.md }]}
        keyboardShouldPersistTaps="handled"
      >
        <View style={styles.topBar}>
          <TouchableOpacity
            style={styles.backPill}
            onPress={() => navigation.goBack()}
            hitSlop={{ top: 10, bottom: 10, left: 10, right: 10 }}
          >
            <Ionicons name="arrow-back" size={20} color={C.primary} />
          </TouchableOpacity>
          <Text style={styles.topBarTitle}>New Request</Text>
        </View>

        <View style={styles.summaryCard}>
          <View style={styles.summaryIconWrap}>
            <Ionicons name="construct-outline" size={22} color={C.textOnPrimary} />
          </View>
          <View style={{ flex: 1 }}>
            <Text style={styles.summaryLabel}>Selected service</Text>
            <Text style={styles.summaryTitle}>{service?.name}</Text>
            <Text style={styles.summaryDesc} numberOfLines={1}>{service?.description}</Text>
          </View>
          <View style={styles.pricePill}>
            <Text style={styles.priceText}>R {service?.price.toFixed(2)}</Text>
          </View>
        </View>

        <Text style={styles.sectionHeading}>Schedule details</Text>

        <AppInput
          label="Requested date"
          icon="calendar-outline"
          placeholder="YYYY-MM-DD"
          value={formData.requestedDate}
          onChangeText={(text) => {
            setFormData({ ...formData, requestedDate: text });
            if (errors.requestedDate) setErrors({ ...errors, requestedDate: '' });
          }}
          error={errors.requestedDate}
        />

        <AppInput
          label="Requested time"
          icon="time-outline"
          placeholder="HH:MM (24-hour format)"
          value={formData.requestedTime}
          onChangeText={(text) => {
            setFormData({ ...formData, requestedTime: text });
            if (errors.requestedTime) setErrors({ ...errors, requestedTime: '' });
          }}
          error={errors.requestedTime}
        />

        <AppInput
          label="Site address"
          icon="location-outline"
          placeholder="Enter the service location"
          value={formData.siteAddress}
          onChangeText={(text) => {
            setFormData({ ...formData, siteAddress: text });
            if (errors.siteAddress) setErrors({ ...errors, siteAddress: '' });
          }}
          multiline
          numberOfLines={3}
          error={errors.siteAddress}
        />

        <AppButton
          title="Submit Request"
          icon="paper-plane-outline"
          loading={loading}
          onPress={handleSubmit}
        />
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: C.background,
  },
  scrollContent: {
    padding: spacing.xl,
    paddingBottom: spacing.xxxl,
  },
  topBar: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: spacing.xl,
  },
  backPill: {
    width: 38,
    height: 38,
    borderRadius: 19,
    backgroundColor: C.surface,
    borderWidth: 1,
    borderColor: C.border,
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: spacing.md,
  },
  topBarTitle: {
    fontFamily: fontFamily.heading,
    fontSize: 20,
    color: C.text,
  },
  summaryCard: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: C.primary,
    borderRadius: radii.lg,
    padding: spacing.lg,
    marginBottom: spacing.xxl,
  },
  summaryIconWrap: {
    width: 44,
    height: 44,
    borderRadius: radii.md,
    backgroundColor: C.primarySoft,
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: spacing.md,
  },
  summaryLabel: {
    fontFamily: fontFamily.body,
    fontSize: 11,
    color: C.textOnPrimaryMuted,
    textTransform: 'uppercase',
    letterSpacing: 0.6,
  },
  summaryTitle: {
    fontFamily: fontFamily.headingMedium,
    fontSize: 16,
    color: C.textOnPrimary,
    marginTop: 2,
  },
  summaryDesc: {
    fontFamily: fontFamily.body,
    fontSize: 12,
    color: C.textOnPrimaryMuted,
    marginTop: 2,
  },
  pricePill: {
    backgroundColor: C.accent,
    borderRadius: radii.pill,
    paddingHorizontal: spacing.md,
    paddingVertical: 6,
    marginLeft: spacing.sm,
  },
  priceText: {
    fontFamily: fontFamily.headingMedium,
    fontSize: 13,
    color: C.primary,
  },
  sectionHeading: {
    fontFamily: fontFamily.headingMedium,
    fontSize: 16,
    color: C.text,
    marginBottom: spacing.lg,
  },
});
