import React, { useEffect, useMemo, useState } from 'react';
import {
  View,
  Text,
  FlatList,
  TouchableOpacity,
  StyleSheet,
  RefreshControl,
  TextInput,
  Image,
  ScrollView,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { collection, onSnapshot } from 'firebase/firestore';
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

const formatDuration = (minutes?: number) => {
  if (!minutes) return 'Flexible';
  if (minutes < 60) return `${minutes} min`;
  const h = Math.floor(minutes / 60);
  const m = minutes % 60;
  return m ? `${h}h ${m}m` : `${h}h`;
};

const CATEGORY_STYLE: Record<string, { color: string; tint: string; icon: keyof typeof Ionicons.glyphMap }> = {
  Cleaning: { color: C.teal, tint: C.tealTint, icon: 'sparkles' },
  Commercial: { color: C.violet, tint: C.violetTint, icon: 'business' },
  Outdoor: { color: C.leaf, tint: C.leafTint, icon: 'leaf' },
  'Pest Control': { color: C.coral, tint: C.coralTint, icon: 'bug' },
};
const DEFAULT_CAT_STYLE = { color: C.accentDark, tint: C.goldTint, icon: 'construct' as const };

const badgeFor = (name: string) => {
  const n = name.toLowerCase();
  if (n === 'deep cleaning') return 'popular' as const;
  if (n === 'office cleaning') return 'trending' as const;
  return null;
};

export default function ServicesScreen({ navigation }: Props) {
  const { userProfile } = useAuth();
  const [services, setServices] = useState<Service[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [search, setSearch] = useState('');
  const [category, setCategory] = useState('all');

  useEffect(() => {
    const unsubscribe = onSnapshot(collection(db, 'services'), (snapshot) => {
      const servicesData: Service[] = [];
      snapshot.forEach((doc) => {
        servicesData.push({ id: doc.id, ...doc.data() } as Service);
      });
      servicesData.sort((a, b) => a.name.localeCompare(b.name));
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

  const categories = useMemo(() => {
    const cats = new Set<string>();
    services.forEach((s) => cats.add(s.category || 'General'));
    return ['all', ...Array.from(cats).sort()];
  }, [services]);

  const filtered = useMemo(() => {
    const term = search.trim().toLowerCase();
    return services.filter((s) => {
      if (category !== 'all' && (s.category || 'General') !== category) return false;
      if (term && !s.name.toLowerCase().includes(term) && !(s.description || '').toLowerCase().includes(term)) {
        return false;
      }
      return true;
    });
  }, [services, search, category]);

  const onRefresh = () => {
    setRefreshing(true);
  };

  const renderService = ({ item }: { item: Service }) => {
    const unavailable = !item.isActive;
    const catStyle = CATEGORY_STYLE[item.category || ''] || DEFAULT_CAT_STYLE;

    return (
      <View style={[styles.serviceCard, unavailable && styles.cardUnavailable]}>
        {unavailable ? (
          <View style={[styles.badge, styles.badgeUnavailable]}>
            <Text style={styles.badgeText}>Unavailable</Text>
          </View>
        ) : badgeFor(item.name) ? (
          <View style={[styles.badge, badgeFor(item.name) === 'popular' ? styles.badgePopular : styles.badgeTrending]}>
            <Text style={styles.badgeText}>{badgeFor(item.name)}</Text>
          </View>
        ) : null}

        <View style={[styles.imageArea, { backgroundColor: catStyle.tint }]}>
          {item.imageUrl ? (
            <Image source={{ uri: item.imageUrl }} style={styles.image} resizeMode="cover" />
          ) : (
            <View style={styles.imagePlaceholder}>
              <View style={[styles.iconBubble, { backgroundColor: 'rgba(255,255,255,0.75)' }]}>
                <Ionicons name={catStyle.icon} size={40} color={catStyle.color} />
              </View>
            </View>
          )}
        </View>

        <View style={styles.content}>
          <Text style={styles.serviceName}>{item.name}</Text>
          <Text style={styles.serviceDescription} numberOfLines={2}>
            {item.description}
          </Text>

          <View style={styles.featureRow}>
            <View style={[styles.featureTag, { backgroundColor: C.skyTint }]}>
              <Ionicons name="time-outline" size={11} color={C.sky} />
              <Text style={[styles.featureText, { color: C.sky }]}>{formatDuration(item.duration)}</Text>
            </View>
            <View style={[styles.featureTag, { backgroundColor: C.goldTint }]}>
              <Ionicons name="star" size={11} color={C.accentDark} />
              <Text style={[styles.featureText, { color: C.accentDark }]}>Professional</Text>
            </View>
            <View style={[styles.featureTag, { backgroundColor: C.successTint }]}>
              <Ionicons name="shield-checkmark-outline" size={11} color={C.success} />
              <Text style={[styles.featureText, { color: C.success }]}>Insured</Text>
            </View>
          </View>

          <View style={styles.footer}>
            {item.price > 0 && (
              <View>
                <Text style={styles.price}>R {item.price.toFixed(2)}</Text>
                <Text style={styles.priceNote}>indicative price</Text>
              </View>
            )}
            {unavailable ? (
              <View style={[styles.bookButton, styles.bookButtonDisabled, styles.grow]}>
                <Text style={styles.bookButtonText}>Unavailable</Text>
              </View>
            ) : (
              <View style={[styles.actions, item.price <= 0 && styles.grow]}>
                <TouchableOpacity
                  style={[styles.quoteButton, item.price <= 0 && styles.grow]}
                  activeOpacity={0.85}
                  onPress={() => navigation.navigate('Quotation', { service: item })}
                >
                  <Text style={styles.quoteButtonText}>Quotation</Text>
                </TouchableOpacity>
                <TouchableOpacity
                  style={[styles.bookButton, item.price <= 0 && styles.grow]}
                  activeOpacity={0.85}
                  onPress={() => navigation.navigate('NewRequest', { service: item })}
                >
                  <Text style={styles.bookButtonText}>Book Now</Text>
                </TouchableOpacity>
              </View>
            )}
          </View>
        </View>
      </View>
    );
  };

  if (loading) {
    return <LoadingScreen />;
  }

  return (
    <View style={styles.container}>
      <AppHeader
        title={userProfile ? `Hello, ${userProfile.firstName}` : 'Our Services'}
        subtitle="Discover the range of professional services we offer"
      />

      <FlatList
        data={filtered}
        renderItem={renderService}
        keyExtractor={(item) => item.id}
        contentContainerStyle={styles.listContent}
        ListHeaderComponent={
          <>
            <View style={styles.searchBox}>
              <Ionicons name="search-outline" size={17} color={C.textMuted} />
              <TextInput
                style={styles.searchInput}
                placeholder="Search services by name or description..."
                placeholderTextColor={C.textMuted}
                value={search}
                onChangeText={setSearch}
                autoCapitalize="none"
                autoCorrect={false}
              />
              {search.length > 0 && (
                <TouchableOpacity onPress={() => setSearch('')} hitSlop={{ top: 8, bottom: 8, left: 8, right: 8 }}>
                  <Ionicons name="close-circle" size={17} color={C.textMuted} />
                </TouchableOpacity>
              )}
            </View>

            <ScrollView
              horizontal
              showsHorizontalScrollIndicator={false}
              contentContainerStyle={styles.chipRow}
            >
              {categories.map((cat) => {
                const active = category === cat;
                return (
                  <TouchableOpacity
                    key={cat}
                    style={[styles.chip, active && styles.chipActive]}
                    onPress={() => setCategory(cat)}
                  >
                    <Text style={[styles.chipText, active && styles.chipTextActive]}>
                      {cat === 'all' ? 'All Categories' : cat}
                    </Text>
                  </TouchableOpacity>
                );
              })}
            </ScrollView>

            <View style={styles.resultsRow}>
              <Text style={styles.resultsCount}>
                {filtered.length} service{filtered.length !== 1 ? 's' : ''} available
              </Text>
              <TouchableOpacity onPress={() => navigation.navigate('CustomerTabs', { screen: 'MyRequests' })}>
                <Text style={styles.viewBookings}>View Bookings →</Text>
              </TouchableOpacity>
            </View>
          </>
        }
        ListEmptyComponent={
          <EmptyState
            icon="search-outline"
            title="No Services Found"
            message="We couldn't find any services matching your criteria. Try adjusting your search or filters."
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
  searchBox: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: C.surface,
    borderRadius: radii.lg,
    borderWidth: 1,
    borderColor: C.border,
    paddingHorizontal: spacing.md,
    paddingVertical: 10,
    gap: spacing.sm,
    marginBottom: spacing.md,
  },
  searchInput: {
    flex: 1,
    fontFamily: fontFamily.body,
    fontSize: 14,
    color: C.text,
    padding: 0,
  },
  chipRow: {
    gap: spacing.sm,
    paddingBottom: spacing.md,
  },
  chip: {
    backgroundColor: C.surface,
    borderRadius: radii.pill,
    borderWidth: 1,
    borderColor: C.border,
    paddingHorizontal: spacing.lg,
    paddingVertical: 7,
  },
  chipActive: {
    backgroundColor: C.accent,
    borderColor: C.accent,
  },
  chipText: {
    fontFamily: fontFamily.bodyBold,
    fontSize: 12.5,
    color: C.textLight,
  },
  chipTextActive: {
    color: C.primary,
  },
  resultsRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: spacing.md,
  },
  resultsCount: {
    fontFamily: fontFamily.body,
    fontSize: 12.5,
    color: C.textMuted,
  },
  viewBookings: {
    fontFamily: fontFamily.bodyBold,
    fontSize: 12.5,
    color: C.accentDark,
  },
  serviceCard: {
    backgroundColor: C.surface,
    borderRadius: radii.xl,
    borderWidth: 1,
    borderColor: C.border,
    overflow: 'hidden',
    marginBottom: spacing.lg,
    ...shadows.card,
  },
  cardUnavailable: {
    opacity: 0.65,
  },
  badge: {
    position: 'absolute',
    top: spacing.md,
    right: spacing.md,
    zIndex: 2,
    borderRadius: radii.pill,
    paddingHorizontal: spacing.sm + 2,
    paddingVertical: 3,
  },
  badgePopular: {
    backgroundColor: C.accent,
  },
  badgeTrending: {
    backgroundColor: C.success,
  },
  badgeUnavailable: {
    backgroundColor: C.textMuted,
  },
  badgeText: {
    fontFamily: fontFamily.bodyBold,
    fontSize: 10.5,
    color: C.textOnPrimary,
    textTransform: 'uppercase',
    letterSpacing: 0.4,
  },
  imageArea: {
    height: 120,
    backgroundColor: C.surfaceSoft,
  },
  image: {
    width: '100%',
    height: '100%',
  },
  imagePlaceholder: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  iconBubble: {
    width: 76,
    height: 76,
    borderRadius: 38,
    alignItems: 'center',
    justifyContent: 'center',
  },
  content: {
    padding: spacing.lg,
  },
  serviceName: {
    fontFamily: fontFamily.heading,
    fontSize: 16,
    color: C.text,
  },
  serviceDescription: {
    fontFamily: fontFamily.body,
    fontSize: 12.5,
    color: C.textLight,
    lineHeight: 18,
    marginTop: 4,
    minHeight: 36,
  },
  featureRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 6,
    marginTop: spacing.sm,
  },
  featureTag: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    backgroundColor: C.neutralTint,
    borderRadius: radii.pill,
    paddingHorizontal: 9,
    paddingVertical: 4,
  },
  featureText: {
    fontFamily: fontFamily.bodyBold,
    fontSize: 11,
    color: C.textLight,
  },
  footer: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    borderTopWidth: 1,
    borderTopColor: C.border,
    marginTop: spacing.md,
    paddingTop: spacing.md,
  },
  price: {
    fontFamily: fontFamily.headingMedium,
    fontSize: 15,
    color: C.accent,
  },
  priceNote: {
    fontFamily: fontFamily.body,
    fontSize: 10.5,
    color: C.textMuted,
    marginTop: 1,
  },

  actions: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
  },
  grow: {
    flex: 1,
  },
  quoteButton: {
    borderRadius: radii.md,
    borderWidth: 1.5,
    borderColor: C.teal,
    paddingHorizontal: spacing.md,
    paddingVertical: 10,
    alignItems: 'center',
    justifyContent: 'center',
  },
  quoteButtonText: {
    fontFamily: fontFamily.bodyBold,
    fontSize: 13,
    color: C.teal,
  },
  bookButton: {
    backgroundColor: C.accent,
    borderRadius: radii.md,
    paddingHorizontal: spacing.md,
    paddingVertical: 10,
    alignItems: 'center',
    justifyContent: 'center',
  },
  bookButtonDisabled: {
    backgroundColor: C.textMuted,
  },
  bookButtonText: {
    fontFamily: fontFamily.bodyBold,
    fontSize: 13,
    color: C.primary,
  },
});
