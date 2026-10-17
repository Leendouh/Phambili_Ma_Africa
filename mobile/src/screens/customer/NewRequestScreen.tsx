import React, { useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  Alert,
  KeyboardAvoidingView,
  Modal,
  Platform,
  ScrollView,
  TouchableOpacity,
} from 'react-native';
import { Calendar } from 'react-native-calendars';
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

const TIME_SLOTS: string[] = (() => {
  const slots: string[] = [];
  for (let h = 7; h <= 18; h++) {
    slots.push(`${String(h).padStart(2, '0')}:00`);
    if (h < 18) slots.push(`${String(h).padStart(2, '0')}:30`);
  }
  return slots;
})();

const now = new Date();
const TODAY_ISO = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;

const MONTH_NAMES = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

function formatDisplayDate(iso: string): string {
  const [y, m, d] = iso.split('-').map(Number);
  if (!y || !m || !d) return iso;
  return `${d} ${MONTH_NAMES[m - 1]} ${y}`;
}

function PickerField({
  label,
  icon,
  value,
  placeholder,
  error,
  onPress,
}: {
  label: string;
  icon: keyof typeof Ionicons.glyphMap;
  value: string;
  placeholder: string;
  error?: string;
  onPress: () => void;
}) {
  return (
    <View style={{ marginBottom: spacing.lg }}>
      <Text style={styles.fieldLabel}>{label}</Text>
      <TouchableOpacity
        style={[styles.fieldWrap, error ? { borderColor: C.danger } : null]}
        onPress={onPress}
        activeOpacity={0.7}
      >
        <Ionicons name={icon} size={18} color={C.textMuted} style={{ marginRight: 10 }} />
        <Text style={[styles.fieldText, !value && { color: C.textMuted }]}>
          {value || placeholder}
        </Text>
        <Ionicons name="chevron-down" size={18} color={C.textMuted} />
      </TouchableOpacity>
      {!!error && <Text style={styles.errorText}>{error}</Text>}
    </View>
  );
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
  const [showDatePicker, setShowDatePicker] = useState(false);
  const [showTimePicker, setShowTimePicker] = useState(false);

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
            <Ionicons name="construct-outline" size={22} color={C.primary} />
          </View>
          <View style={{ flex: 1 }}>
            <Text style={styles.summaryLabel}>Selected service</Text>
            <Text style={styles.summaryTitle}>{service?.name}</Text>
            <Text style={styles.summaryDesc} numberOfLines={1}>{service?.description}</Text>
          </View>
          <View style={styles.pricePill}>
            <Text style={styles.priceText}>
              {service?.price ? `R ${service.price.toFixed(2)}` : 'Custom Quote'}
            </Text>
          </View>
        </View>

        <Text style={styles.sectionHeading}>Schedule details</Text>

        <PickerField
          label="Requested date"
          icon="calendar-outline"
          placeholder="Select a date"
          value={formData.requestedDate ? formatDisplayDate(formData.requestedDate) : ''}
          error={errors.requestedDate}
          onPress={() => setShowDatePicker(true)}
        />

        <PickerField
          label="Requested time"
          icon="time-outline"
          placeholder="Select a time (07:00 - 18:00)"
          value={formData.requestedTime}
          error={errors.requestedTime}
          onPress={() => setShowTimePicker(true)}
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

      <Modal
        visible={showDatePicker}
        transparent
        animationType="fade"
        onRequestClose={() => setShowDatePicker(false)}
      >
        <View style={styles.modalBackdrop}>
          <View style={styles.modalCard}>
            <Text style={styles.modalTitle}>Select a date</Text>
            <Calendar
              minDate={TODAY_ISO}
              markedDates={
                formData.requestedDate
                  ? { [formData.requestedDate]: { selected: true, selectedColor: C.accent, selectedTextColor: C.primary } }
                  : {}
              }
              onDayPress={(day: { dateString: string }) => {
                setFormData({ ...formData, requestedDate: day.dateString });
                if (errors.requestedDate) setErrors({ ...errors, requestedDate: '' });
                setShowDatePicker(false);
              }}
              theme={{
                calendarBackground: C.surface,
                monthTextColor: C.text,
                dayTextColor: C.text,
                textDisabledColor: C.textMuted,
                textSectionTitleColor: C.textMuted,
                arrowColor: C.primary,
                todayTextColor: C.accentDark,
                selectedDayBackgroundColor: C.accent,
                selectedDayTextColor: C.primary,
                textDayFontFamily: fontFamily.body,
                textMonthFontFamily: fontFamily.headingMedium,
                textDayHeaderFontFamily: fontFamily.bodyBold,
              }}
            />
            <TouchableOpacity style={styles.modalCancel} onPress={() => setShowDatePicker(false)}>
              <Text style={styles.modalCancelText}>Cancel</Text>
            </TouchableOpacity>
          </View>
        </View>
      </Modal>

      <Modal
        visible={showTimePicker}
        transparent
        animationType="fade"
        onRequestClose={() => setShowTimePicker(false)}
      >
        <View style={styles.modalBackdrop}>
          <View style={styles.modalCard}>
            <Text style={styles.modalTitle}>Select a time</Text>
            <ScrollView style={styles.timeList} showsVerticalScrollIndicator={false}>
              {TIME_SLOTS.map((slot) => {
                const selected = formData.requestedTime === slot;
                return (
                  <TouchableOpacity
                    key={slot}
                    style={[styles.timeRow, selected && styles.timeRowSelected]}
                    onPress={() => {
                      setFormData({ ...formData, requestedTime: slot });
                      if (errors.requestedTime) setErrors({ ...errors, requestedTime: '' });
                      setShowTimePicker(false);
                    }}
                  >
                    <Ionicons
                      name="time-outline"
                      size={16}
                      color={selected ? C.primary : C.textMuted}
                    />
                    <Text style={[styles.timeRowText, selected && styles.timeRowTextSelected]}>
                      {slot}
                    </Text>
                    {selected && <Ionicons name="checkmark" size={18} color={C.primary} />}
                  </TouchableOpacity>
                );
              })}
            </ScrollView>
            <TouchableOpacity style={styles.modalCancel} onPress={() => setShowTimePicker(false)}>
              <Text style={styles.modalCancelText}>Cancel</Text>
            </TouchableOpacity>
          </View>
        </View>
      </Modal>
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
    backgroundColor: C.accent,
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
  fieldLabel: {
    fontFamily: fontFamily.bodyBold,
    fontSize: 13,
    color: C.text,
    marginBottom: spacing.sm,
  },
  fieldWrap: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: C.surface,
    borderWidth: 1.5,
    borderColor: C.border,
    borderRadius: radii.md,
    paddingHorizontal: spacing.md,
    minHeight: 50,
  },
  fieldText: {
    flex: 1,
    fontFamily: fontFamily.body,
    fontSize: 15,
    color: C.text,
    paddingVertical: 13,
  },
  errorText: {
    fontFamily: fontFamily.body,
    fontSize: 12,
    color: C.danger,
    marginTop: 6,
  },
  modalBackdrop: {
    flex: 1,
    backgroundColor: 'rgba(8, 29, 51, 0.55)',
    alignItems: 'center',
    justifyContent: 'center',
    padding: spacing.xl,
  },
  modalCard: {
    backgroundColor: C.surface,
    borderRadius: radii.lg,
    padding: spacing.lg,
    width: '100%',
    maxWidth: 380,
  },
  modalTitle: {
    fontFamily: fontFamily.headingMedium,
    fontSize: 16,
    color: C.text,
    marginBottom: spacing.md,
    textAlign: 'center',
  },
  modalCancel: {
    alignItems: 'center',
    paddingVertical: spacing.md,
    marginTop: spacing.sm,
  },
  modalCancelText: {
    fontFamily: fontFamily.bodyBold,
    fontSize: 14,
    color: C.textMuted,
  },
  timeList: {
    maxHeight: 320,
  },
  timeRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    paddingVertical: spacing.md,
    paddingHorizontal: spacing.sm,
    borderRadius: radii.md,
  },
  timeRowSelected: {
    backgroundColor: C.accent,
  },
  timeRowText: {
    flex: 1,
    fontFamily: fontFamily.body,
    fontSize: 15,
    color: C.text,
  },
  timeRowTextSelected: {
    fontFamily: fontFamily.bodyBold,
    color: C.primary,
  },
});
