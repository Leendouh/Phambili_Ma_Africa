import React from 'react';
import { View, Text, StyleSheet } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { AppButton, Card, InfoRow } from '../../components/ui';
import { lightColors as C } from '../../theme/colors';
import { fontFamily } from '../../theme/typography';
import { radii } from '../../theme/radii';
import { spacing } from '../../theme/spacing';

interface Props {
  route: any;
  navigation: any;
}

export default function RequestConfirmationScreen({ route, navigation }: Props) {
  const insets = useSafeAreaInsets();
  const { refNumber, serviceName, requestedDate, requestedTime, siteAddress } = route.params ?? {};

  const goToRequests = () =>
    navigation.navigate('AuthNavigator', {
      screen: 'CustomerTabs',
      params: { screen: 'MyRequests' },
    });

  const goToServices = () =>
    navigation.navigate('AuthNavigator', {
      screen: 'CustomerTabs',
      params: { screen: 'Services' },
    });

  return (
    <View style={[styles.container, { paddingTop: insets.top + spacing.xxxl, paddingBottom: insets.bottom + spacing.xl }]}>
      <View style={styles.checkWrap}>
        <Ionicons name="checkmark" size={40} color={C.success} />
      </View>

      <Text style={styles.title}>Request submitted</Text>
      <Text style={styles.subtitle}>
        Our team will review your request and assign a team member shortly.
      </Text>

      <View style={styles.refCard}>
        <Text style={styles.refLabel}>Reference number</Text>
        <Text style={styles.refNumber}>{refNumber}</Text>
        <Text style={styles.refHint}>Keep this number to track your request</Text>
      </View>

      <Card style={styles.summary}>
        <InfoRow icon="construct-outline" label="Service" value={serviceName ?? ''} />
        <InfoRow icon="calendar-outline" label="Date" value={`${requestedDate ?? ''} at ${requestedTime ?? ''}`} />
        <InfoRow icon="location-outline" label="Address" value={siteAddress ?? ''} />
      </Card>

      <View style={styles.actions}>
        <AppButton
          title="Track My Request"
          icon="document-text-outline"
          onPress={goToRequests}
        />
        <AppButton
          title="Book Another Service"
          variant="outline"
          onPress={goToServices}
          style={{ marginTop: spacing.md }}
        />
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: C.background,
    paddingHorizontal: spacing.xl,
  },
  checkWrap: {
    width: 80,
    height: 80,
    borderRadius: 40,
    backgroundColor: C.successTint,
    alignItems: 'center',
    justifyContent: 'center',
    alignSelf: 'center',
    marginBottom: spacing.xl,
  },
  title: {
    fontFamily: fontFamily.heading,
    fontSize: 24,
    color: C.text,
    textAlign: 'center',
  },
  subtitle: {
    fontFamily: fontFamily.body,
    fontSize: 13.5,
    color: C.textLight,
    textAlign: 'center',
    marginTop: spacing.sm,
    paddingHorizontal: spacing.lg,
  },
  refCard: {
    alignItems: 'center',
    backgroundColor: C.primary,
    borderRadius: radii.lg,
    padding: spacing.xl,
    marginTop: spacing.xxl,
  },
  refLabel: {
    fontFamily: fontFamily.body,
    fontSize: 11,
    color: C.textOnPrimaryMuted,
    textTransform: 'uppercase',
    letterSpacing: 0.8,
  },
  refNumber: {
    fontFamily: fontFamily.heading,
    fontSize: 26,
    color: C.textOnPrimary,
    letterSpacing: 1.5,
    marginTop: 6,
  },
  refHint: {
    fontFamily: fontFamily.body,
    fontSize: 12,
    color: C.textOnPrimaryMuted,
    marginTop: 6,
  },
  summary: {
    marginTop: spacing.md,
  },
  actions: {
    marginTop: 'auto',
  },
});
