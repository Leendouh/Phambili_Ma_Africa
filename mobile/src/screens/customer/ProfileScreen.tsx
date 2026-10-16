import React from 'react';
import {
  View,
  Text,
  StyleSheet,
  Alert,
  ScrollView,
} from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useAuth } from '../../context/AuthContext';
import { AppButton, Card, InfoRow, SectionTitle, LoadingScreen } from '../../components/ui';
import { lightColors as C } from '../../theme/colors';
import { fontFamily } from '../../theme/typography';
import { radii } from '../../theme/radii';
import { spacing } from '../../theme/spacing';

export default function ProfileScreen({ navigation }: any) {
  const insets = useSafeAreaInsets();
  const { userProfile, logout } = useAuth();

  const handleLogout = () => {
    Alert.alert(
      'Logout',
      'Are you sure you want to logout?',
      [
        { text: 'Cancel', style: 'cancel' },
        {
          text: 'Logout',
          style: 'destructive',
          onPress: async () => {
            try {
              await logout();
            } catch (error: any) {
              Alert.alert('Error', error.message);
            }
          },
        },
      ]
    );
  };

  if (!userProfile) {
    return <LoadingScreen />;
  }

  const initials = `${userProfile.firstName?.[0] ?? ''}${userProfile.lastName?.[0] ?? ''}`.toUpperCase();

  return (
    <ScrollView style={styles.container} bounces={false}>
      <View style={[styles.header, { paddingTop: insets.top + spacing.xxl }]}>
        <View style={styles.avatar}>
          <Text style={styles.avatarText}>{initials}</Text>
        </View>
        <Text style={styles.name}>
          {userProfile.firstName} {userProfile.lastName}
        </Text>
        <Text style={styles.email}>{userProfile.email}</Text>
        <View style={styles.roleChip}>
          <Text style={styles.roleText}>{userProfile.role === 'employee' ? 'Team member' : 'Customer'}</Text>
        </View>
      </View>

      <View style={styles.content}>
        <Card style={styles.section}>
          <SectionTitle>Personal information</SectionTitle>
          <InfoRow icon="call-outline" label="Phone" value={userProfile.phone || 'Not provided'} />
          <InfoRow icon="location-outline" label="Address" value={userProfile.address || 'Not provided'} />
          <InfoRow
            icon="calendar-outline"
            label="Member since"
            value={
              userProfile.dateRegistered
                ? new Date(userProfile.dateRegistered).toLocaleDateString()
                : 'N/A'
            }
          />
        </Card>

        <AppButton
          title="Sign Out"
          variant="danger"
          icon="log-out-outline"
          onPress={handleLogout}
          style={{ marginTop: spacing.sm }}
        />
      </View>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: C.background,
  },
  header: {
    backgroundColor: C.primary,
    paddingBottom: spacing.xxxl,
    alignItems: 'center',
    borderBottomLeftRadius: radii.xl + 4,
    borderBottomRightRadius: radii.xl + 4,
  },
  avatar: {
    width: 88,
    height: 88,
    borderRadius: 44,
    backgroundColor: C.accent,
    justifyContent: 'center',
    alignItems: 'center',
    marginBottom: spacing.lg,
    borderWidth: 3,
    borderColor: 'rgba(255,255,255,0.25)',
  },
  avatarText: {
    fontFamily: fontFamily.heading,
    fontSize: 30,
    color: C.primary,
  },
  name: {
    fontFamily: fontFamily.heading,
    fontSize: 22,
    color: C.textOnPrimary,
  },
  email: {
    fontFamily: fontFamily.body,
    fontSize: 13,
    color: C.textOnPrimaryMuted,
    marginTop: 4,
  },
  roleChip: {
    backgroundColor: C.primarySoft,
    borderRadius: radii.pill,
    paddingHorizontal: spacing.md,
    paddingVertical: 5,
    marginTop: spacing.md,
  },
  roleText: {
    fontFamily: fontFamily.bodyBold,
    fontSize: 11,
    color: C.textOnPrimaryMuted,
    textTransform: 'uppercase',
    letterSpacing: 0.8,
  },
  content: {
    padding: spacing.xl,
  },
  section: {
    marginBottom: spacing.md,
  },
});
