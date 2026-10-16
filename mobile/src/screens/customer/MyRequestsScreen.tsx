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
import { AppButton, AppHeader, EmptyState, LoadingScreen, StatusBadge } from '../../components/ui';
import { lightColors as C } from '../../theme/colors';
import { fontFamily } from '../../theme/typography';
import { radii } from '../../theme/radii';
import { shadows } from '../../theme/shadows';
import { spacing } from '../../theme/spacing';

export default function MyRequestsScreen({ navigation }: any) {
  const { userProfile } = useAuth();
  const [requests, setRequests] = useState<ServiceRequest[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [filter, setFilter] = useState<'active' | 'history'>('active');

  useEffect(() => {
    if (!userProfile) return;

    const q = query(
      collection(db, 'requests'),
      where('customerId', '==', userProfile.uid)
    );

    const unsubscribe = onSnapshot(q, (snapshot) => {
      const requestsData: ServiceRequest[] = [];
      snapshot.forEach((doc) => {
        requestsData.push({
          id: doc.id,
          ...doc.data(),
        } as ServiceRequest);
      });
      requestsData.sort((a, b) => new Date(b.dateCreated).getTime() - new Date(a.dateCreated).getTime());
      setRequests(requestsData);
      setLoading(false);
      setRefreshing(false);
    }, (error) => {
      console.error('Error fetching requests:', error);
      setLoading(false);
      setRefreshing(false);
    });

    return () => unsubscribe();
  }, [userProfile]);

  const onRefresh = () => {
    setRefreshing(true);
  };

  const renderRequest = ({ item }: { item: ServiceRequest }) => (
    <TouchableOpacity
      style={styles.requestCard}
      activeOpacity={0.8}
      onPress={() => navigation.navigate('RequestDetail', { request: item })}
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

      {item.hasFeedback && (
        <View style={styles.feedbackBadge}>
          <Ionicons name="star" size={12} color={C.accentDark} />
          <Text style={styles.feedbackText}>Feedback submitted</Text>
        </View>
      )}
    </TouchableOpacity>
  );

  if (loading) {
    return <LoadingScreen />;
  }

  const isHistory = (item: ServiceRequest) =>
    item.status === 'Completed' || item.status === 'Rejected';

  const filtered = requests.filter((item) =>
    filter === 'active' ? !isHistory(item) : isHistory(item)
  );
  const activeCount = requests.filter((item) => !isHistory(item)).length;
  const historyCount = requests.length - activeCount;

  const filterChips = (
    <View style={styles.chipsRow}>
      {(['active', 'history'] as const).map((key) => {
        const count = key === 'active' ? activeCount : historyCount;
        const selected = filter === key;
        return (
          <TouchableOpacity
            key={key}
            style={[styles.chip, selected && styles.chipSelected]}
            activeOpacity={0.8}
            onPress={() => setFilter(key)}
          >
            <Text style={[styles.chipText, selected && styles.chipTextSelected]}>
              {key === 'active' ? 'Active' : 'History'} ({count})
            </Text>
          </TouchableOpacity>
        );
      })}
    </View>
  );

  return (
    <View style={styles.container}>
      <AppHeader title="My Requests" subtitle="Track the status of your bookings" />

      {requests.length === 0 ? (
        <EmptyState
          icon="document-text-outline"
          title="No requests yet"
          message="When you book a service it will appear here."
        >
          <AppButton
            title="Browse Services"
            onPress={() => navigation.navigate('Services')}
          />
        </EmptyState>
      ) : (
        <FlatList
          data={filtered}
          renderItem={renderRequest}
          keyExtractor={(item) => item.id}
          contentContainerStyle={styles.listContent}
          ListHeaderComponent={filterChips}
          ListEmptyComponent={
            <View style={styles.emptyList}>
              <Text style={styles.emptyListText}>
                {filter === 'active'
                  ? 'No active requests'
                  : 'No completed or rejected requests yet'}
              </Text>
            </View>
          }
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
    flexGrow: 1,
  },
  chipsRow: {
    flexDirection: 'row',
    gap: spacing.sm,
    marginBottom: spacing.lg,
  },
  chip: {
    paddingHorizontal: spacing.lg,
    paddingVertical: 8,
    borderRadius: radii.pill,
    backgroundColor: C.surface,
    borderWidth: 1,
    borderColor: C.border,
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
  emptyList: {
    paddingVertical: spacing.huge,
    alignItems: 'center',
  },
  emptyListText: {
    fontFamily: fontFamily.body,
    fontSize: 13.5,
    color: C.textMuted,
  },
  requestCard: {
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
  feedbackBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 5,
    alignSelf: 'flex-start',
    backgroundColor: C.goldTint,
    paddingHorizontal: spacing.sm,
    paddingVertical: 4,
    borderRadius: radii.pill,
    marginTop: spacing.md,
  },
  feedbackText: {
    fontFamily: fontFamily.bodyBold,
    fontSize: 11,
    color: C.accentDark,
  },
});
