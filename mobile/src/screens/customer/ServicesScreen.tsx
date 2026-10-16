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
import { collection, query, where, onSnapshot } from 'firebase/firestore';
import { db } from '../../firebase';
import { useAuth } from '../../context/AuthContext';
import { Service } from '../../types';
import { AppHeader, EmptyState, LoadingScreen } from '../../components/ui';
import { lightColors as C } from '../../theme/colors';
import { fontFamily } from '../../theme/typography';
import { radii } from '../../theme/radii';
import { shadows } from '../../theme/shadows';
import { spacing } from '../../theme/spacing';

interface Props {
  navigation: any;
}

export default function ServicesScreen({ navigation }: Props) {
  const { userProfile } = useAuth();
  const [services, setServices] = useState<Service[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  useEffect(() => {
    const q = query(collection(db, 'services'), where('isActive', '==', true));

    const unsubscribe = onSnapshot(q, (snapshot) => {
      const servicesData: Service[] = [];
      snapshot.forEach((doc) => {
        servicesData.push({
          id: doc.id,
          ...doc.data(),
        } as Service);
      });
      setServices(servicesData);
      setLoading(false);
      setRefreshing(false);
    }, (error) => {
      console.error('Error fetching services:', error);
      setLoading(false);
      setRefreshing(false);
    });

    return () => unsubscribe();
  }, []);

  const onRefresh = () => {
    setRefreshing(true);
  };

  const renderService = ({ item }: { item: Service }) => (
    <TouchableOpacity
      style={styles.serviceCard}
      activeOpacity={0.8}
      onPress={() => navigation.navigate('NewRequest', { service: item })}
    >
      <View style={styles.iconWrap}>
        <Ionicons name="construct-outline" size={22} color={C.primary} />
      </View>
      <View style={styles.serviceInfo}>
        <Text style={styles.serviceName}>{item.name}</Text>
        <Text style={styles.serviceDescription} numberOfLines={2}>
          {item.description}
        </Text>
        <Text style={styles.servicePrice}>R {item.price.toFixed(2)}</Text>
      </View>
      <View style={styles.chevronWrap}>
        <Ionicons name="chevron-forward" size={18} color={C.textMuted} />
      </View>
    </TouchableOpacity>
  );

  if (loading) {
    return <LoadingScreen />;
  }

  return (
    <View style={styles.container}>
      <AppHeader
        title={userProfile ? `Hello, ${userProfile.firstName}` : 'Services'}
        subtitle="What can we help you with today?"
      />

      <FlatList
        data={services}
        renderItem={renderService}
        keyExtractor={(item) => item.id}
        contentContainerStyle={styles.listContent}
        ListHeaderComponent={<Text style={styles.sectionHeading}>Available services</Text>}
        ListEmptyComponent={
          <EmptyState
            icon="briefcase-outline"
            title="No services available"
            message="Please check back later."
          />
        }
        refreshControl={
          <RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={C.primary} />
        }
      />
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
  sectionHeading: {
    fontFamily: fontFamily.headingMedium,
    fontSize: 16,
    color: C.text,
    marginBottom: spacing.lg,
  },
  serviceCard: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: C.surface,
    borderRadius: radii.lg,
    borderWidth: 1,
    borderColor: C.border,
    padding: spacing.lg,
    marginBottom: spacing.md,
    ...shadows.card,
  },
  iconWrap: {
    width: 46,
    height: 46,
    borderRadius: radii.md,
    backgroundColor: C.neutralTint,
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: spacing.md,
  },
  serviceInfo: {
    flex: 1,
  },
  serviceName: {
    fontFamily: fontFamily.headingMedium,
    fontSize: 15,
    color: C.text,
  },
  serviceDescription: {
    fontFamily: fontFamily.body,
    fontSize: 12.5,
    color: C.textLight,
    marginTop: 2,
  },
  servicePrice: {
    fontFamily: fontFamily.headingMedium,
    fontSize: 14,
    color: C.accent,
    marginTop: 6,
  },
  chevronWrap: {
    marginLeft: spacing.sm,
  },
});
