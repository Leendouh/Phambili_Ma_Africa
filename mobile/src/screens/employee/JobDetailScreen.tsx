import React, { useEffect, useState } from 'react';
import {
  View,
  Text,
  ScrollView,
  StyleSheet,
  Alert,
  Linking,
  TouchableOpacity,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useAuth } from '../../context/AuthContext';
import { updateDoc, doc, addDoc, collection, onSnapshot } from 'firebase/firestore';
import { db } from '../../firebase';
import { ServiceRequest } from '../../types';
import { getFirebaseErrorMessage } from '../../utils/errorHandler';
import { AppButton, Card, InfoRow, SectionTitle, StatusBadge } from '../../components/ui';
import { lightColors as C } from '../../theme/colors';
import { fontFamily } from '../../theme/typography';
import { radii } from '../../theme/radii';
import { spacing } from '../../theme/spacing';

interface Props {
  route: any;
  navigation: any;
}

export default function JobDetailScreen({ route, navigation }: Props) {
  const insets = useSafeAreaInsets();
  const { userProfile } = useAuth();
  const [job, setJob] = useState<ServiceRequest>(route.params?.job);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    const unsubscribe = onSnapshot(doc(db, 'requests', route.params?.job?.id), (snap) => {
      if (snap.exists()) setJob({ id: snap.id, ...snap.data() } as ServiceRequest);
    });
    return unsubscribe;
  }, [route.params?.job?.id]);

  const updateStatus = async (newStatus: string) => {
    if (!userProfile) return;

    setLoading(true);
    try {
      await updateDoc(doc(db, 'requests', job.id), {
        status: newStatus,
      });

      // Create notification for customer
      await addDoc(collection(db, 'notifications'), {
        recipientId: job.customerId,
        requestId: job.id,
        message: `Request ${job.refNumber} is now ${newStatus}`,
        isRead: false,
        dateCreated: new Date().toISOString(),
      });

      Alert.alert('Success', `Job status updated to ${newStatus}`);
    } catch (error) {
      Alert.alert('Error', getFirebaseErrorMessage(error));
    } finally {
      setLoading(false);
    }
  };

  const handleStartJob = () => {
    Alert.alert(
      'Start Job',
      'Are you sure you want to mark this job as In Progress?',
      [
        { text: 'Cancel', style: 'cancel' },
        {
          text: 'Start',
          onPress: () => updateStatus('In Progress'),
        },
      ]
    );
  };

  const handleCompleteJob = () => {
    Alert.alert(
      'Complete Job',
      'Are you sure you want to mark this job as Completed?',
      [
        { text: 'Cancel', style: 'cancel' },
        {
          text: 'Complete',
          onPress: () => updateStatus('Completed'),
        },
      ]
    );
  };

  return (
    <ScrollView style={styles.container} bounces={false}>
      <View style={[styles.header, { paddingTop: insets.top + spacing.md }]}>
        <TouchableOpacity
          style={styles.backPill}
          onPress={() => navigation.goBack()}
          hitSlop={{ top: 10, bottom: 10, left: 10, right: 10 }}
        >
          <Ionicons name="arrow-back" size={20} color={C.textOnPrimary} />
        </TouchableOpacity>
        <View style={styles.headerRow}>
          <View>
            <Text style={styles.headerLabel}>Job reference</Text>
            <Text style={styles.refNumber}>{job.refNumber}</Text>
          </View>
          <StatusBadge status={job.status} />
        </View>
      </View>

      <View style={styles.content}>
        <Card style={styles.section}>
          <SectionTitle>Service details</SectionTitle>
          <InfoRow icon="construct-outline" label="Service" value={job.serviceName} />
        </Card>

        <Card style={styles.section}>
          <SectionTitle>Customer</SectionTitle>
          <InfoRow icon="person-outline" label="Name" value={job.customerName || 'Not available'} />
          {job.customerPhone ? (
            <TouchableOpacity
              activeOpacity={0.7}
              onPress={() => Linking.openURL(`tel:${job.customerPhone}`)}
            >
              <InfoRow icon="call-outline" label="Phone (tap to call)" value={job.customerPhone} />
            </TouchableOpacity>
          ) : (
            <InfoRow icon="call-outline" label="Phone" value="Not available" />
          )}
        </Card>

        <Card style={styles.section}>
          <SectionTitle>Schedule & location</SectionTitle>
          <InfoRow icon="calendar-outline" label="Date" value={job.requestedDate} />
          <InfoRow icon="time-outline" label="Time" value={job.requestedTime} />
          <InfoRow icon="location-outline" label="Address" value={job.siteAddress} />
        </Card>

        {job.status === 'Approved' && (
          <AppButton
            title="Start Job"
            icon="play-outline"
            loading={loading}
            onPress={handleStartJob}
          />
        )}

        {job.status === 'In Progress' && (
          <AppButton
            title="Mark Complete"
            icon="checkmark-done-outline"
            loading={loading}
            onPress={handleCompleteJob}
          />
        )}

        {job.status === 'Completed' && (
          <View style={styles.completedMessage}>
            <Ionicons name="checkmark-circle" size={20} color={C.success} />
            <Text style={styles.completedText}>This job has been completed</Text>
          </View>
        )}
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
    paddingHorizontal: spacing.xl,
    paddingBottom: spacing.xl,
    borderBottomLeftRadius: radii.xl + 4,
    borderBottomRightRadius: radii.xl + 4,
  },
  backPill: {
    width: 38,
    height: 38,
    borderRadius: 19,
    backgroundColor: C.primarySoft,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: spacing.md,
  },
  headerRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  headerLabel: {
    fontFamily: fontFamily.body,
    fontSize: 11,
    color: C.textOnPrimaryMuted,
    textTransform: 'uppercase',
    letterSpacing: 0.6,
  },
  refNumber: {
    fontFamily: fontFamily.heading,
    fontSize: 20,
    color: C.textOnPrimary,
    marginTop: 4,
  },
  content: {
    padding: spacing.xl,
  },
  section: {
    marginBottom: spacing.md,
  },
  completedMessage: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: spacing.sm,
    backgroundColor: C.successTint,
    borderRadius: radii.md,
    padding: spacing.lg,
    marginTop: spacing.sm,
  },
  completedText: {
    fontFamily: fontFamily.bodyBold,
    fontSize: 14,
    color: C.success,
  },
});
