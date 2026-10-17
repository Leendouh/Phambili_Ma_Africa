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
import { Service } from '../../types';
import { validateEmail } from '../../utils/validators';
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

const PROPERTY_TYPES = ['House', 'Apartment', 'Office', 'Commercial Space'];
const FREQUENCIES = ['One-time', 'Weekly', 'Bi-weekly', 'Monthly'];
const TIME_SLOTS = ['08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00'];

const MONTH_NAMES = [
  'January', 'February', 'March', 'April', 'May', 'June',
  'July', 'August', 'September', 'October', 'November', 'December',
];
const DAY_LETTERS = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'];

const pad = (n: number) => String(n).padStart(2, '0');
const toISODate = (y: number, m: number, d: number) => `${y}-${pad(m + 1)}-${pad(d)}`;
const to12h = (hhmm: string) => {
  const h = Number(hhmm.slice(0, 2));
  const ampm = h < 12 ? 'AM' : 'PM';
  const h12 = h % 12 === 0 ? 12 : h % 12;
  return `${pad(h12)}:00 ${ampm}`;
};

function Chip({ label, selected, onPress }: { label: string; selected: boolean; onPress: () => void }) {
  return (
    <TouchableOpacity
      style={[styles.chip, selected && styles.chipSelected]}
      onPress={onPress}
      activeOpacity={0.8}
    >
      <Text style={[styles.chipText, selected && styles.chipTextSelected]}>{label}</Text>
    </TouchableOpacity>
  );
}

function MonthCalendar({ selected, onSelect }: { selected: string; onSelect: (iso: string) => void }) {
  const today = new Date();
  const todayMidnight = new Date(today.getFullYear(), today.getMonth(), today.getDate());
  const [view, setView] = useState(() => {
    if (selected) {
      const [y, m] = selected.split('-').map(Number);
      return { year: y, month: m - 1 };
    }
    return { year: today.getFullYear(), month: today.getMonth() };
  });

  const firstDay = new Date(view.year, view.month, 1).getDay();
  const daysInMonth = new Date(view.year, view.month + 1, 0).getDate();
  const cells: (number | null)[] = [
    ...Array<null>(firstDay).fill(null),
    ...Array.from({ length: daysInMonth }, (_, i) => i + 1),
  ];
  while (cells.length % 7 !== 0) cells.push(null);
  const weeks: (number | null)[][] = [];
  for (let i = 0; i < cells.length; i += 7) weeks.push(cells.slice(i, i + 7));

  const changeMonth = (delta: number) => {
    const d = new Date(view.year, view.month + delta, 1);
    setView({ year: d.getFullYear(), month: d.getMonth() });
  };

  return (
    <View style={styles.calendar}>
      <View style={styles.calHeader}>
        <TouchableOpacity onPress={() => changeMonth(-1)} hitSlop={{ top: 8, bottom: 8, left: 8, right: 8 }}>
          <Ionicons name="chevron-back" size={18} color={C.primary} />
        </TouchableOpacity>
        <Text style={styles.calTitle}>{MONTH_NAMES[view.month]} {view.year}</Text>
        <TouchableOpacity onPress={() => changeMonth(1)} hitSlop={{ top: 8, bottom: 8, left: 8, right: 8 }}>
          <Ionicons name="chevron-forward" size={18} color={C.primary} />
        </TouchableOpacity>
      </View>
      <View style={styles.calRow}>
        {DAY_LETTERS.map((d) => (
          <Text key={d} style={styles.calDayLetter}>{d}</Text>
        ))}
      </View>
      {weeks.map((week, w) => (
        <View key={w} style={styles.calRow}>
          {week.map((day, i) => {
            if (day === null) return <View key={`e${i}`} style={styles.calCell} />;
            const cellDate = new Date(view.year, view.month, day);
            const isPast = cellDate < todayMidnight;
            const iso = toISODate(view.year, view.month, day);
            const isSelected = selected === iso;
            return (
              <TouchableOpacity
                key={iso}
                style={[styles.calCell, isSelected && styles.calCellSelected]}
                disabled={isPast}
                onPress={() => onSelect(iso)}
              >
                <Text
                  style={[
                    styles.calDayText,
                    isPast && styles.calDayDisabled,
                    isSelected && styles.calDayTextSelected,
                  ]}
                >
                  {day}
                </Text>
              </TouchableOpacity>
            );
          })}
        </View>
      ))}
    </View>
  );
}

export default function QuotationScreen({ route, navigation }: Props) {
  const insets = useSafeAreaInsets();
  const { userProfile } = useAuth();
  const service: Service = route.params?.service;

  const [propertyType, setPropertyType] = useState('');
  const [frequency, setFrequency] = useState('');
  const [requestedDate, setRequestedDate] = useState('');
  const [requestedTime, setRequestedTime] = useState('');
  const addrParts = (userProfile?.address ?? '').split(',').map((p: string) => p.trim());
  const [street, setStreet] = useState(addrParts[0] ?? '');
  const [city, setCity] = useState(addrParts[1] ?? '');
  const [province, setProvince] = useState(addrParts[2] ?? '');
  const [postal, setPostal] = useState(addrParts[3] ?? '');
  const [specialRequests, setSpecialRequests] = useState('');
  const [fullName, setFullName] = useState(
    `${userProfile?.firstName ?? ''} ${userProfile?.lastName ?? ''}`.trim()
  );
  const [phone, setPhone] = useState(() => {
    const p = (userProfile?.phone ?? '').replace(/\s/g, '');
    if (p.startsWith('+27')) return '0' + p.slice(3);
    if (p.startsWith('27')) return '0' + p.slice(2);
    return p;
  });
  const [email, setEmail] = useState(userProfile?.email ?? '');
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [loading, setLoading] = useState(false);

  const validateForm = () => {
    const e: Record<string, string> = {};
    const today = new Date();
    const todayStr = toISODate(today.getFullYear(), today.getMonth(), today.getDate());

    if (!propertyType) e.propertyType = 'Property type is required';
    if (!frequency) e.frequency = 'Service frequency is required';

    if (!requestedDate) {
      e.requestedDate = 'Preferred date is required';
    } else if (requestedDate < todayStr) {
      e.requestedDate = 'Cannot book for past dates';
    } else if (requestedDate === todayStr && today.getHours() >= 12) {
      const t = new Date(today.getTime() + 86400000);
      e.requestedDate = `Same-day bookings must be made before 12:00 PM. Please select ${toISODate(t.getFullYear(), t.getMonth(), t.getDate())} or later`;
    }

    if (!requestedTime) {
      e.requestedTime = 'Preferred time is required';
    } else if (
      requestedDate === todayStr &&
      requestedTime <= `${pad(today.getHours())}:${pad(today.getMinutes())}`
    ) {
      e.requestedTime = 'Cannot book for past times. Please select a future time';
    }

    if (!street.trim()) e.street = 'Street address is required';
    if (!city.trim()) e.city = 'City is required';
    if (!province.trim()) e.province = 'Province is required';
    if (!/^\d{4}$/.test(postal.trim())) e.postal = 'Postal code must be exactly 4 digits (e.g. 2000)';
    if (!fullName.trim()) e.fullName = 'Full name is required';
    if (!/^0\d{9}$/.test(phone.replace(/\s/g, ''))) {
      e.phone = 'Phone must be exactly 10 digits starting with 0 (e.g. 0821234567)';
    }
    const emailError = validateEmail(email);
    if (emailError) e.email = emailError;

    setErrors(e);
    return Object.keys(e).length === 0;
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
      const siteAddress = [street, city, province, postal]
        .map((s) => s.trim())
        .filter(Boolean)
        .join(', ');

      await addDoc(collection(db, 'requests'), {
        refNumber,
        customerId: userProfile.uid,
        customerName: fullName.trim(),
        customerPhone: phone.replace(/\s/g, ''),
        customerEmail: email.trim(),
        serviceId: service.id,
        serviceName: service.name,
        propertyType,
        cleaningFrequency: frequency,
        specialRequests: specialRequests.trim(),
        duration: service.duration ?? null,
        employeeId: null,
        employeeName: null,
        staffId: null,
        requestedDate,
        requestedTime,
        siteAddress,
        status: 'Pending',
        hasFeedback: false,
        dateCreated: new Date().toISOString(),
      });

      navigation.replace('RequestConfirmation', {
        refNumber,
        serviceName: service.name,
        requestedDate,
        requestedTime: to12h(requestedTime),
        siteAddress,
      });
    } catch (error) {
      Alert.alert('Error', getFirebaseErrorMessage(error));
    } finally {
      setLoading(false);
    }
  };

  const field = (
    label: string,
    value: string,
    set: (v: string) => void,
    opts: { placeholder?: string; errorKey: string; keyboardType?: any; autoCapitalize?: any; multiline?: boolean }
  ) => (
    <AppInput
      label={label}
      placeholder={opts.placeholder}
      value={value}
      onChangeText={(t) => {
        set(t);
        if (errors[opts.errorKey]) setErrors({ ...errors, [opts.errorKey]: '' });
      }}
      keyboardType={opts.keyboardType}
      autoCapitalize={opts.autoCapitalize}
      multiline={opts.multiline}
      error={errors[opts.errorKey]}
    />
  );

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
          <Text style={styles.topBarTitle}>Request Quotation</Text>
        </View>

        <View style={styles.greetingCard}>
          <Text style={styles.greetingTitle}>Hi there,</Text>
          <Text style={styles.greetingText}>
            Thank you for connecting through our online booking system!
          </Text>
          <Text style={styles.greetingText}>
            After you have submitted this, you will receive confirmation where you will select a
            date &amp; time for a virtual consultation to discuss your needs, view your space,
            provide you a quote, and get you booked in with us!
          </Text>
          <Text style={styles.greetingSignoff}>
            Thank you,{'\n'}Phambili Ma-Africa - Trading Enterprise
          </Text>
        </View>

        <View style={styles.summaryCard}>
          <View style={styles.summaryIconWrap}>
            <Ionicons name="sparkles-outline" size={22} color={C.primary} />
          </View>
          <View style={{ flex: 1 }}>
            <Text style={styles.summaryLabel}>Service Details</Text>
            <Text style={styles.summaryTitle}>{service?.name}</Text>
            <Text style={styles.summaryDesc} numberOfLines={1}>{service?.description}</Text>
          </View>
          <View style={styles.pricePill}>
            <Text style={styles.priceText}>
              {service?.price ? `R ${service.price.toFixed(2)}` : 'Custom Quote'}
            </Text>
          </View>
        </View>

        <Text style={styles.label}>Property Type</Text>
        <View style={styles.chipWrap}>
          {PROPERTY_TYPES.map((p) => (
            <Chip key={p} label={p} selected={propertyType === p} onPress={() => {
              setPropertyType(p);
              if (errors.propertyType) setErrors({ ...errors, propertyType: '' });
            }} />
          ))}
        </View>
        {!!errors.propertyType && <Text style={styles.errorText}>{errors.propertyType}</Text>}

        <Text style={styles.label}>Service Frequency</Text>
        <View style={styles.chipWrap}>
          {FREQUENCIES.map((f) => (
            <Chip key={f} label={f} selected={frequency === f} onPress={() => {
              setFrequency(f);
              if (errors.frequency) setErrors({ ...errors, frequency: '' });
            }} />
          ))}
        </View>
        {!!errors.frequency && <Text style={styles.errorText}>{errors.frequency}</Text>}

        <Text style={styles.sectionHeading}>Schedule</Text>

        <Text style={styles.label}>Preferred Date</Text>
        <MonthCalendar
          selected={requestedDate}
          onSelect={(iso) => {
            setRequestedDate(iso);
            if (errors.requestedDate) setErrors({ ...errors, requestedDate: '' });
          }}
        />
        {!!errors.requestedDate && <Text style={styles.errorText}>{errors.requestedDate}</Text>}

        <Text style={styles.label}>Preferred Time</Text>
        <View style={styles.chipWrap}>
          {TIME_SLOTS.map((t) => (
            <Chip key={t} label={to12h(t)} selected={requestedTime === t} onPress={() => {
              setRequestedTime(t);
              if (errors.requestedTime) setErrors({ ...errors, requestedTime: '' });
            }} />
          ))}
        </View>
        {!!errors.requestedTime && <Text style={styles.errorText}>{errors.requestedTime}</Text>}

        <Text style={styles.sectionHeading}>Service Location</Text>
        {field('Street Address', street, setStreet, { placeholder: 'e.g. 12 Main Road', errorKey: 'street', autoCapitalize: 'words' })}
        {field('City', city, setCity, { placeholder: 'e.g. Johannesburg', errorKey: 'city', autoCapitalize: 'words' })}
        {field('Province', province, setProvince, { placeholder: 'e.g. Gauteng', errorKey: 'province', autoCapitalize: 'words' })}
        {field('Postal Code', postal, setPostal, { placeholder: 'e.g. 2000', errorKey: 'postal', keyboardType: 'number-pad' })}

        <Text style={styles.sectionHeading}>Additional Information</Text>
        {field('Special Requirements', specialRequests, setSpecialRequests, { placeholder: 'Anything we should know? (optional)', errorKey: 'specialRequests', multiline: true })}

        <Text style={styles.sectionHeading}>Your Information</Text>
        {field('Full Name', fullName, setFullName, { placeholder: 'Your full name', errorKey: 'fullName', autoCapitalize: 'words' })}
        {field('Phone Number', phone, setPhone, { placeholder: 'e.g. 0821234567', errorKey: 'phone', keyboardType: 'phone-pad' })}
        {field('Email Address', email, setEmail, { placeholder: 'you@example.com', errorKey: 'email', keyboardType: 'email-address', autoCapitalize: 'none' })}

        <View style={styles.buttonRow}>
          <TouchableOpacity
            style={styles.cancelButton}
            onPress={() => navigation.goBack()}
            disabled={loading}
          >
            <Text style={styles.cancelText}>Cancel</Text>
          </TouchableOpacity>
          <View style={{ flex: 1 }}>
            <AppButton
              title="Request Quotation"
              icon="paper-plane-outline"
              loading={loading}
              onPress={handleSubmit}
            />
          </View>
        </View>
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
    marginBottom: spacing.lg,
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
  greetingCard: {
    backgroundColor: C.goldTint,
    borderRadius: radii.lg,
    borderWidth: 1,
    borderColor: C.accent,
    padding: spacing.lg,
    marginBottom: spacing.xl,
  },
  greetingTitle: {
    fontFamily: fontFamily.headingMedium,
    fontSize: 15,
    color: C.text,
    marginBottom: spacing.sm,
  },
  greetingText: {
    fontFamily: fontFamily.body,
    fontSize: 13,
    color: C.textLight,
    lineHeight: 19,
    marginBottom: spacing.sm,
  },
  greetingSignoff: {
    fontFamily: fontFamily.bodyBold,
    fontSize: 13,
    color: C.text,
    lineHeight: 19,
  },
  summaryCard: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: C.primary,
    borderRadius: radii.lg,
    padding: spacing.lg,
    marginBottom: spacing.xl,
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
    marginTop: spacing.xl,
    marginBottom: spacing.md,
  },
  label: {
    fontFamily: fontFamily.bodyBold,
    fontSize: 13,
    color: C.text,
    marginBottom: spacing.sm,
    marginTop: spacing.xs,
  },
  chipWrap: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
    marginBottom: spacing.md,
  },
  chip: {
    backgroundColor: C.surface,
    borderWidth: 1,
    borderColor: C.border,
    borderRadius: radii.pill,
    paddingHorizontal: spacing.lg,
    paddingVertical: 8,
  },
  chipSelected: {
    backgroundColor: C.primary,
    borderColor: C.primary,
  },
  chipText: {
    fontFamily: fontFamily.bodyBold,
    fontSize: 12.5,
    color: C.textLight,
  },
  chipTextSelected: {
    color: C.textOnPrimary,
  },
  errorText: {
    fontFamily: fontFamily.body,
    fontSize: 12,
    color: C.danger,
    marginTop: -spacing.sm,
    marginBottom: spacing.md,
  },
  calendar: {
    backgroundColor: C.surface,
    borderWidth: 1,
    borderColor: C.border,
    borderRadius: radii.lg,
    padding: spacing.md,
    marginBottom: spacing.md,
  },
  calHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: spacing.sm,
    paddingHorizontal: spacing.sm,
  },
  calTitle: {
    fontFamily: fontFamily.headingMedium,
    fontSize: 15,
    color: C.text,
  },
  calRow: {
    flexDirection: 'row',
  },
  calDayLetter: {
    flex: 1,
    textAlign: 'center',
    fontFamily: fontFamily.bodyBold,
    fontSize: 11,
    color: C.textMuted,
    paddingVertical: 4,
  },
  calCell: {
    flex: 1,
    aspectRatio: 1.35,
    alignItems: 'center',
    justifyContent: 'center',
  },
  calCellSelected: {
    backgroundColor: C.primary,
    borderRadius: radii.md,
  },
  calDayText: {
    fontFamily: fontFamily.body,
    fontSize: 14,
    color: C.text,
  },
  calDaySelectedText: {},
  calDayDisabled: {
    color: C.textMuted,
    opacity: 0.4,
  },
  calDayTextSelected: {
    color: C.textOnPrimary,
    fontFamily: fontFamily.bodyBold,
  },
  buttonRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    marginTop: spacing.xl,
  },
  cancelButton: {
    paddingHorizontal: spacing.lg,
    paddingVertical: 12,
    borderRadius: radii.md,
    borderWidth: 1,
    borderColor: C.border,
    backgroundColor: C.surface,
  },
  cancelText: {
    fontFamily: fontFamily.bodyBold,
    fontSize: 14,
    color: C.textLight,
  },
});
