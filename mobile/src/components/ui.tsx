import React from 'react';
import {
  View,
  Text,
  TextInput,
  TouchableOpacity,
  TouchableOpacityProps,
  TextInputProps,
  StyleSheet,
  ActivityIndicator,
  ViewStyle,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { lightColors as C } from '../theme/colors';
import { fontFamily } from '../theme/typography';
import { radii } from '../theme/radii';
import { shadows } from '../theme/shadows';
import { spacing } from '../theme/spacing';

/* ---------- Status ---------- */

const STATUS_META: Record<string, { color: string; tint: string; icon: keyof typeof Ionicons.glyphMap }> = {
  Pending: { color: C.warning, tint: C.warningTint, icon: 'time-outline' },
  Approved: { color: C.info, tint: C.infoTint, icon: 'checkmark-circle-outline' },
  'In Progress': { color: C.progress, tint: C.progressTint, icon: 'construct-outline' },
  Completed: { color: C.success, tint: C.successTint, icon: 'checkmark-done-circle-outline' },
  Rejected: { color: C.danger, tint: C.dangerTint, icon: 'close-circle-outline' },
};

export function statusMeta(status: string) {
  return STATUS_META[status] ?? { color: C.textMuted, tint: C.neutralTint, icon: 'ellipse-outline' as const };
}

export function StatusBadge({ status }: { status: string }) {
  const meta = statusMeta(status);
  return (
    <View style={[s.badge, { backgroundColor: meta.tint }]}>
      <Ionicons name={meta.icon} size={13} color={meta.color} />
      <Text style={[s.badgeText, { color: meta.color }]}>{status}</Text>
    </View>
  );
}

/* ---------- Button ---------- */

interface AppButtonProps extends TouchableOpacityProps {
  title: string;
  variant?: 'primary' | 'outline' | 'danger' | 'accent';
  loading?: boolean;
  icon?: keyof typeof Ionicons.glyphMap;
}

export function AppButton({ title, variant = 'primary', loading, icon, style, disabled, ...rest }: AppButtonProps) {
  const bg = { primary: C.primary, accent: C.accent, danger: C.danger, outline: 'transparent' }[variant];
  const fg = variant === 'outline' ? C.primary : '#FFFFFF';
  return (
    <TouchableOpacity
      activeOpacity={0.85}
      style={[
        s.button,
        { backgroundColor: bg },
        variant === 'outline' && s.buttonOutline,
        variant === 'primary' && shadows.button,
        (disabled || loading) && { opacity: 0.6 },
        style,
      ]}
      disabled={disabled || loading}
      {...rest}
    >
      {loading ? (
        <ActivityIndicator color={fg} />
      ) : (
        <>
          {icon && <Ionicons name={icon} size={18} color={fg} style={{ marginRight: 8 }} />}
          <Text style={[s.buttonText, { color: fg }]}>{title}</Text>
        </>
      )}
    </TouchableOpacity>
  );
}

/* ---------- Input ---------- */

interface AppInputProps extends TextInputProps {
  label: string;
  error?: string;
  icon?: keyof typeof Ionicons.glyphMap;
  rightIcon?: keyof typeof Ionicons.glyphMap;
  onRightIconPress?: () => void;
}

export function AppInput({ label, error, icon, rightIcon, onRightIconPress, style, ...rest }: AppInputProps) {
  return (
    <View style={{ marginBottom: spacing.lg }}>
      <Text style={s.inputLabel}>{label}</Text>
      <View style={[s.inputWrap, error && { borderColor: C.danger }]}>
        {icon && <Ionicons name={icon} size={18} color={C.textMuted} style={{ marginRight: 10 }} />}
        <TextInput
          style={[s.input, style]}
          placeholderTextColor={C.textMuted}
          {...rest}
        />
        {rightIcon && (
          <TouchableOpacity onPress={onRightIconPress} hitSlop={{ top: 10, bottom: 10, left: 10, right: 10 }}>
            <Ionicons name={rightIcon} size={20} color={C.textMuted} />
          </TouchableOpacity>
        )}
      </View>
      {!!error && <Text style={s.errorText}>{error}</Text>}
    </View>
  );
}

/* ---------- Card ---------- */

export function Card({ children, style }: { children: React.ReactNode; style?: ViewStyle }) {
  return <View style={[s.card, style]}>{children}</View>;
}

export function SectionTitle({ children }: { children: React.ReactNode }) {
  return <Text style={s.sectionTitle}>{children}</Text>;
}

/* ---------- Header ---------- */

interface AppHeaderProps {
  title: string;
  subtitle?: string;
  children?: React.ReactNode;
}

export function AppHeader({ title, subtitle, children }: AppHeaderProps) {
  const insets = useSafeAreaInsets();
  return (
    <View style={[s.header, { paddingTop: insets.top + spacing.lg }]}>
      <Text style={s.headerTitle}>{title}</Text>
      {subtitle && <Text style={s.headerSubtitle}>{subtitle}</Text>}
      {children}
    </View>
  );
}

/* ---------- Misc ---------- */

export function EmptyState({ icon, title, message, children }: { icon: keyof typeof Ionicons.glyphMap; title: string; message?: string; children?: React.ReactNode }) {
  return (
    <View style={s.empty}>
      <View style={s.emptyIconWrap}>
        <Ionicons name={icon} size={34} color={C.textMuted} />
      </View>
      <Text style={s.emptyTitle}>{title}</Text>
      {message && <Text style={s.emptyMessage}>{message}</Text>}
      {children && <View style={{ marginTop: spacing.xl }}>{children}</View>}
    </View>
  );
}

export function LoadingScreen() {
  return (
    <View style={s.center}>
      <ActivityIndicator size="large" color={C.primary} />
    </View>
  );
}

export function InfoRow({ icon, label, value }: { icon: keyof typeof Ionicons.glyphMap; label: string; value: string }) {
  return (
    <View style={s.infoRow}>
      <View style={s.infoIconWrap}>
        <Ionicons name={icon} size={16} color={C.primary} />
      </View>
      <View style={{ flex: 1 }}>
        <Text style={s.infoLabel}>{label}</Text>
        <Text style={s.infoValue}>{value}</Text>
      </View>
    </View>
  );
}

const s = StyleSheet.create({
  badge: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 5,
    paddingHorizontal: spacing.sm,
    paddingVertical: 4,
    borderRadius: radii.pill,
  },
  badgeText: {
    fontFamily: fontFamily.bodyBold,
    fontSize: 11,
  },
  button: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 15,
    borderRadius: radii.md,
  },
  buttonOutline: {
    borderWidth: 1.5,
    borderColor: C.primary,
  },
  buttonText: {
    fontFamily: fontFamily.headingMedium,
    fontSize: 15,
  },
  inputLabel: {
    fontFamily: fontFamily.bodyBold,
    fontSize: 13,
    color: C.text,
    marginBottom: spacing.sm,
  },
  inputWrap: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: C.surface,
    borderWidth: 1.5,
    borderColor: C.border,
    borderRadius: radii.md,
    paddingHorizontal: spacing.md,
    minHeight: 50,
  },
  input: {
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
  card: {
    backgroundColor: C.surface,
    borderRadius: radii.lg,
    padding: spacing.lg,
    borderWidth: 1,
    borderColor: C.border,
    ...shadows.card,
  },
  sectionTitle: {
    fontFamily: fontFamily.headingMedium,
    fontSize: 15,
    color: C.text,
    marginBottom: spacing.md,
  },
  header: {
    backgroundColor: C.primary,
    paddingHorizontal: spacing.xl,
    paddingBottom: spacing.xl,
    borderBottomLeftRadius: radii.xl + 4,
    borderBottomRightRadius: radii.xl + 4,
  },
  headerTitle: {
    fontFamily: fontFamily.heading,
    fontSize: 24,
    color: C.textOnPrimary,
  },
  headerSubtitle: {
    fontFamily: fontFamily.body,
    fontSize: 13,
    color: C.textOnPrimaryMuted,
    marginTop: 4,
  },
  empty: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    padding: spacing.xxxl,
  },
  emptyIconWrap: {
    width: 72,
    height: 72,
    borderRadius: 36,
    backgroundColor: C.neutralTint,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: spacing.lg,
  },
  emptyTitle: {
    fontFamily: fontFamily.headingMedium,
    fontSize: 16,
    color: C.text,
    marginBottom: 6,
  },
  emptyMessage: {
    fontFamily: fontFamily.body,
    fontSize: 13,
    color: C.textMuted,
    textAlign: 'center',
  },
  center: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: C.background,
  },
  infoRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    paddingVertical: spacing.sm,
  },
  infoIconWrap: {
    width: 34,
    height: 34,
    borderRadius: 10,
    backgroundColor: C.neutralTint,
    alignItems: 'center',
    justifyContent: 'center',
  },
  infoLabel: {
    fontFamily: fontFamily.body,
    fontSize: 11,
    color: C.textMuted,
    textTransform: 'uppercase',
    letterSpacing: 0.6,
  },
  infoValue: {
    fontFamily: fontFamily.bodyBold,
    fontSize: 14,
    color: C.text,
    marginTop: 1,
  },
});
