import React, { useEffect, useState } from 'react';
import {
  View,
  Text,
  FlatList,
  TouchableOpacity,
  StyleSheet,
  RefreshControl,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { useAuth } from '../../context/AuthContext';
import { query, where, onSnapshot, collection } from 'firebase/firestore';
import { db } from '../../firebase';
import { ServiceRequest } from '../../types';
import { AppHeader, EmptyState, LoadingScreen, StatusBadge } from '../../components/ui';
import { lightColors as C } from '../../theme/colors';
import { fontFamily } from '../../theme/typography';
import { radii } from '../../theme/radii';
import { shadows } from '../../theme/shadows';
import { spacing } from '../../theme/spacing';

export default function MyJobsScreen({ navigation }: any) {
  const { userProfile } = useAuth();
  const [jobs, setJobs] = useState<ServiceRequest[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  useEffect(() => {
    if (!userProfile) return;

    const q = query(
      collection(db, 'requests'),
      where('employeeId', '==', userProfile.uid)
    );

    const unsubscribe = onSnapshot(q, (snapshot) => {
      const jobsData: ServiceRequest[] = [];
      snapshot.forEach((doc) => {
        jobsData.push({
          id: doc.id,
          ...doc.data(),
        } as ServiceRequest);
      });
      jobsData.sort((a, b) => new Date(b.dateCreated).getTime() - new Date(a.dateCreated).getTime());
      setJobs(jobsData);
      setLoading(false);
      setRefreshing(false);
    }, (error) => {
      console.error('Error fetching jobs:', error);
      setLoading(false);
      setRefreshing(false);
    });

    return () => unsubscribe();
  }, [userProfile]);

  const onRefresh = () => {
    setRefreshing(true);
  };

  const renderJob = ({ item }: { item: ServiceRequest }) => (
    <TouchableOpacity
      style={styles.jobCard}
      activeOpacity={0.8}
      onPress={() => navigation.navigate('JobDetail', { job: item })}
    >
      <View style={styles.cardHeader}>
        <Text style={styles.refNumber}>{item.refNumber}</Text>
        <StatusBadge status={item.status} />
      </View>

      <Text style={styles.serviceName}>{item.serviceName}</Text>

      <View style={styles.metaRow}>
        <Ionicons name="calendar-outline" size={13} color={C.textMuted} />
        <Text style={styles.metaText}>{item.requestedDate}</Text>
        <Ionicons name="time-outline" size={13} color={C.textMuted} style={styles.metaIcon} />
        <Text style={styles.metaText}>{item.requestedTime}</Text>
      </View>
      <View style={styles.metaRow}>
        <Ionicons name="location-outline" size={13} color={C.textMuted} />
        <Text style={styles.metaText} numberOfLines={1}>{item.siteAddress}</Text>
      </View>
      <View style={styles.metaRow}>
        <Ionicons name="person-outline" size={13} color={C.textMuted} />
        <Text style={styles.metaText} numberOfLines={1}>
          {item.customerName || 'Customer'}
          {item.customerPhone ? `  •  ${item.customerPhone}` : ''}
        </Text>
      </View>
    </TouchableOpacity>
  );

  if (loading) {
    return <LoadingScreen />;
  }

  return (
    <View style={styles.container}>
      <AppHeader title="My Jobs" subtitle="Jobs assigned to you" />

      {jobs.length === 0 ? (
        <EmptyState
          icon="briefcase-outline"
          title="No jobs assigned"
          message="When staff assigns you a job it will appear here."
        />
      ) : (
        <FlatList
          data={jobs}
          renderItem={renderJob}
          keyExtractor={(item) => item.id}
          contentContainerStyle={styles.listContent}
          refreshControl={
            <RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={C.primary} />
          }
        />
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: C.background,
  },
  listContent: {
    padding: spacing.xl,
  },
  jobCard: {
    backgroundColor: C.surface,
    borderRadius: radii.lg,
    borderWidth: 1,
    borderColor: C.border,
    padding: spacing.lg,
    marginBottom: spacing.md,
    ...shadows.card,
  },
  cardHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: spacing.sm,
  },
  refNumber: {
    fontFamily: fontFamily.bodyBold,
    fontSize: 12,
    color: C.textMuted,
    letterSpacing: 0.5,
  },
  serviceName: {
    fontFamily: fontFamily.headingMedium,
    fontSize: 16,
    color: C.text,
    marginBottom: spacing.sm,
  },
  metaRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginTop: 4,
    gap: 5,
  },
  metaIcon: {
    marginLeft: spacing.md,
  },
  metaText: {
    fontFamily: fontFamily.body,
    fontSize: 12.5,
    color: C.textLight,
    flexShrink: 1,
  },
});
