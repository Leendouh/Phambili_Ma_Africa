import React, { useEffect, useState } from 'react';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { View, Text, StyleSheet, ScrollView, TouchableOpacity, Image, TextInput } from 'react-native';
import { Ionicons, FontAwesome5 } from '@expo/vector-icons';
import { Calendar } from 'react-native-calendars';
import { CustomerTabsParamList } from './types';
import { useTheme } from '../theme';
import apiClient from '../api/client';
import { useAuthStore } from '../store/authStore';
import { useCartStore } from '../store/cartStore';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

const Tab = createBottomTabNavigator<CustomerTabsParamList>();

type ServiceItem = {
  ID?: number;
  id?: number;
  Name?: string;
  name?: string;
  Description?: string;
  description?: string;
  Duration?: number;
  duration?: number;
  Category?: string;
  category?: string;
  Image_URL?: string | null;
  image_url?: string | null;
  Is_Available?: boolean | number | string;
  is_available?: boolean | number | string;
};

type ProductItem = {
  ID?: number;
  id?: number;
  Name?: string;
  name?: string;
  Description?: string;
  description?: string;
  Price?: number | string;
  price?: number | string;
  Category?: string;
  category?: string;
  Stock_Quantity?: number;
  stock_quantity?: number;
  Image_URL?: string | null;
  image_url?: string | null;
  Is_Available?: boolean | number | string;
  is_available?: boolean | number | string;
};

type BookingItem = {
  ID?: number;
  id?: number;
  Service_Name?: string;
  service_name?: string;
  Date?: string;
  date?: string;
  Time?: string;
  time?: string;
  Status?: string;
  status?: string;
  Address?: string;
  address?: string;
  Quoted_Amount?: number | string;
  quoted_amount?: number | string;
  Property_Type?: string;
  property_type?: string;
  Cleaning_Frequency?: string;
  cleaning_frequency?: string;
  Special_Instructions?: string;
  special_instructions?: string;
};

const styles = StyleSheet.create({
  screen: { flex: 1 },
  headerWrap: { paddingTop: 18, paddingHorizontal: 20, paddingBottom: 12, borderBottomWidth: 1 },
  header: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' },
  logoWrap: { flexDirection: 'row', alignItems: 'center' },
  logoBadge: { width: 38, height: 38, borderRadius: 12, alignItems: 'center', justifyContent: 'center', marginRight: 10 },
  logoBadgeText: { fontSize: 18, fontWeight: '800', color: '#ffffff' },
  logoText: { fontSize: 22, fontWeight: '800', letterSpacing: 0.2 },
  cartButton: { width: 42, height: 42, borderRadius: 14, alignItems: 'center', justifyContent: 'center', position: 'relative', borderWidth: 1 },
  cartBadge: { position: 'absolute', right: -4, top: -4, minWidth: 18, height: 18, borderRadius: 9, backgroundColor: '#ef4444', alignItems: 'center', justifyContent: 'center', paddingHorizontal: 4 },
  cartBadgeText: { color: '#ffffff', fontSize: 10, fontWeight: '700' },
  content: { paddingHorizontal: 20, paddingTop: 20, paddingBottom: 32 },
  hero: { borderRadius: 24, padding: 22, marginBottom: 22, overflow: 'hidden', borderWidth: 1, borderColor: 'rgba(255,255,255,0.18)', backgroundColor: '#0066cc' },
  heroTag: { fontSize: 12, fontWeight: '700', letterSpacing: 1.2, textTransform: 'uppercase', color: '#dbeafe', marginBottom: 8 },
  heroTitle: { fontSize: 30, fontWeight: '800', color: '#ffffff', marginBottom: 8 },
  heroSubtitle: { fontSize: 15, color: '#e0f2fe', lineHeight: 22, marginBottom: 18 },
  heroActions: { flexDirection: 'row', alignItems: 'center', flexWrap: 'wrap' },
  heroButton: { backgroundColor: '#ffffff', paddingHorizontal: 18, paddingVertical: 12, borderRadius: 14, marginRight: 12, marginBottom: 10 },
  heroButtonText: { color: '#0f172a', fontSize: 14, fontWeight: '700' },
  heroLink: { flexDirection: 'row', alignItems: 'center', marginBottom: 10 },
  heroLinkText: { color: '#ffffff', fontSize: 14, fontWeight: '600', marginRight: 4 },
  heroStats: { flexDirection: 'row', flexWrap: 'wrap', marginTop: 8 },
  statCard: { backgroundColor: 'rgba(255,255,255,0.12)', borderRadius: 14, paddingHorizontal: 12, paddingVertical: 10, marginRight: 8, marginBottom: 8, borderWidth: 1, borderColor: 'rgba(255,255,255,0.18)' },
  statValue: { color: '#ffffff', fontSize: 16, fontWeight: '800' },
  statLabel: { color: '#dbeafe', fontSize: 11, fontWeight: '600', marginTop: 2 },
  section: { marginBottom: 24 },
  sectionHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 14 },
  sectionTitle: { fontSize: 20, fontWeight: '800' },
  sectionLink: { fontSize: 13, fontWeight: '700' },
  serviceGrid: { flexDirection: 'row', flexWrap: 'wrap', justifyContent: 'space-between' },
  serviceCard: { width: '48%', borderRadius: 20, borderWidth: 1, padding: 16, marginBottom: 14 },
  serviceIconWrap: { width: 52, height: 52, borderRadius: 16, alignItems: 'center', justifyContent: 'center', marginBottom: 14 },
  serviceName: { fontSize: 16, fontWeight: '700', marginBottom: 6 },
  serviceDescription: { fontSize: 12, lineHeight: 18, marginBottom: 14 },
  serviceFooter: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  servicePrice: { fontSize: 13, fontWeight: '800' },
  productGrid: { flexDirection: 'row', flexWrap: 'wrap', justifyContent: 'space-between' },
  productCard: { width: '48%', borderRadius: 20, borderWidth: 1, padding: 14, marginBottom: 14 },
  productImageWrap: { width: '100%', height: 120, borderRadius: 18, alignItems: 'center', justifyContent: 'center', marginBottom: 12, overflow: 'hidden' },
  productImage: { width: '100%', height: '100%' },
  productEmoji: { fontSize: 40 },
  productName: { fontSize: 15, fontWeight: '700', marginBottom: 6 },
  productMeta: { fontSize: 12, marginBottom: 10 },
  productFooter: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  productPrice: { fontSize: 15, fontWeight: '800' },
  addButton: { width: 30, height: 30, borderRadius: 10, alignItems: 'center', justifyContent: 'center' },
  bookingsContainer: { flex: 1, paddingTop: 18 },
  bookingHero: { borderRadius: 20, borderWidth: 1, padding: 18, marginHorizontal: 20, marginBottom: 16 },
  bookingHeroHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 12 },
  bookingHeroTitle: { fontSize: 20, fontWeight: '800' },
  bookingHeroButton: { borderRadius: 12, paddingHorizontal: 12, paddingVertical: 8 },
  bookingHeroButtonText: { fontSize: 12, fontWeight: '800', color: '#ffffff' },
  bookingSummary: { flexDirection: 'row', justifyContent: 'space-between', marginTop: 14 },
  summaryChip: { borderRadius: 12, paddingHorizontal: 12, paddingVertical: 8, borderWidth: 1, flex: 1, marginRight: 8 },
  summaryChipValue: { fontSize: 18, fontWeight: '800' },
  summaryChipLabel: { fontSize: 10, fontWeight: '700', textTransform: 'uppercase', letterSpacing: 0.6, marginTop: 2 },
  featureRow: { flexDirection: 'row', flexWrap: 'wrap', marginBottom: 16 },
  featurePill: { borderRadius: 999, paddingHorizontal: 12, paddingVertical: 8, borderWidth: 1, marginRight: 8, marginBottom: 8 },
  featurePillText: { fontSize: 12, fontWeight: '700' },
  tabContainer: { flexDirection: 'row', paddingHorizontal: 20, borderBottomWidth: 1, marginBottom: 12 },
  tab: { flex: 1, alignItems: 'center', justifyContent: 'center', paddingVertical: 12, borderBottomWidth: 2 },
  tabText: { fontSize: 14, fontWeight: '700' },
  bookingsList: { flex: 1, paddingHorizontal: 20, paddingBottom: 24 },
  bookingCard: { borderRadius: 18, borderWidth: 1, padding: 16, marginBottom: 14 },
  bookingHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 12 },
  bookingService: { fontSize: 17, fontWeight: '800', flexShrink: 1 },
  statusBadge: { borderRadius: 999, paddingHorizontal: 10, paddingVertical: 6, marginLeft: 8 },
  statusText: { fontSize: 10, fontWeight: '800', textTransform: 'uppercase', letterSpacing: 0.6 },
  bookingDetails: { gap: 8 },
  detailItem: { flexDirection: 'row', alignItems: 'center', gap: 8 },
  detailText: { fontSize: 13, flexShrink: 1 },
  bookingFooter: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginTop: 14, paddingTop: 12, borderTopWidth: 1 },
  bookingPrice: { fontSize: 15, fontWeight: '800' },
  emptyState: { alignItems: 'center', justifyContent: 'center', paddingVertical: 50 },
  emptyText: { fontSize: 15, fontWeight: '600', marginTop: 14 },
  emptyActionButton: { marginTop: 16, paddingHorizontal: 16, paddingVertical: 10, borderRadius: 12 },
  emptyActionText: { fontSize: 14, fontWeight: '700' },
  profileScreen: { flex: 1 },
  profileWrap: { paddingHorizontal: 20, paddingTop: 18, paddingBottom: 30 },
  profileCard: { borderRadius: 20, borderWidth: 1, padding: 18, marginBottom: 18, flexDirection: 'row', alignItems: 'center' },
  avatar: { width: 62, height: 62, borderRadius: 18, alignItems: 'center', justifyContent: 'center', marginRight: 14 },
  avatarText: { fontSize: 20, fontWeight: '800', color: '#ffffff' },
  profileName: { fontSize: 18, fontWeight: '800', marginBottom: 4 },
  profileEmail: { fontSize: 13 },
  infoSection: { borderRadius: 20, borderWidth: 1, paddingVertical: 6, paddingHorizontal: 14, marginBottom: 16 },
  sectionRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', paddingVertical: 10 },
  sectionHeading: { fontSize: 16, fontWeight: '800' },
  editButton: { borderRadius: 10, paddingHorizontal: 10, paddingVertical: 6, borderWidth: 1 },
  editButtonText: { fontSize: 12, fontWeight: '700' },
  infoRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', paddingVertical: 12, borderTopWidth: 1 },
  infoLabel: { fontSize: 13, fontWeight: '600' },
  infoValue: { fontSize: 13, fontWeight: '600', textAlign: 'right', flexShrink: 1, marginLeft: 18 },
  listSection: { borderRadius: 20, borderWidth: 1, padding: 14, marginBottom: 14 },
  listCard: { borderRadius: 18, padding: 16, borderWidth: 1, marginBottom: 12 },
  listHeader: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginBottom: 10 },
  listHeading: { fontSize: 16, fontWeight: '700' },
  listPrice: { fontSize: 15, fontWeight: '800' },
  listDescription: { fontSize: 12, marginBottom: 10 },
  formWrapper: { paddingHorizontal: 20, paddingTop: 20, paddingBottom: 30 },
  formCard: { borderRadius: 20, borderWidth: 1, padding: 16, marginBottom: 16 },
  fieldLabel: { fontSize: 13, fontWeight: '700', marginBottom: 8 },
  fieldInput: { borderWidth: 1, borderRadius: 12, paddingHorizontal: 12, paddingVertical: 12, fontSize: 14 },
  fieldRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 12 },
  submitButton: { borderRadius: 14, paddingVertical: 14, alignItems: 'center', justifyContent: 'center', marginTop: 8 },
  submitText: { color: '#ffffff', fontSize: 15, fontWeight: '700' },
  secondaryButton: { borderRadius: 14, paddingVertical: 12, alignItems: 'center', justifyContent: 'center', marginTop: 8, borderWidth: 1 },
  dangerButton: { marginTop: 8, marginBottom: 18, borderRadius: 14, paddingVertical: 14, alignItems: 'center', justifyContent: 'center' },
  logoutButtonText: { color: '#ffffff', fontSize: 15, fontWeight: '700' },
  loadingText: { fontSize: 14, fontWeight: '600', textAlign: 'center', paddingVertical: 20 },
  helperText: { fontSize: 12, marginTop: 6 },
  formHeader: { fontSize: 22, fontWeight: '800', marginBottom: 8 },
  formSubText: { fontSize: 14, marginBottom: 16 },
  cartList: { paddingHorizontal: 20, paddingTop: 20, paddingBottom: 32 },
  cartItem: { borderRadius: 18, borderWidth: 1, padding: 16, marginBottom: 12, flexDirection: 'row', alignItems: 'center' },
  cartItemImage: { width: 60, height: 60, borderRadius: 16, marginRight: 12 },
  cartItemMeta: { flex: 1 },
  quantityRow: { flexDirection: 'row', alignItems: 'center', marginTop: 8 },
  quantityButton: { width: 32, height: 32, borderRadius: 8, alignItems: 'center', justifyContent: 'center', borderWidth: 1 },
  quantityValue: { fontSize: 14, fontWeight: '700', marginHorizontal: 8 },
  cartSummary: { borderRadius: 18, borderWidth: 1, padding: 16, marginTop: 12 },
  cartSummaryRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 8 },
  pickerTrigger: { borderWidth: 1, borderRadius: 12, paddingHorizontal: 12, paddingVertical: 12, flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' },
  pickerMenu: { borderRadius: 12, overflow: 'hidden', marginTop: 8, borderWidth: 1 },
  pickerOption: { paddingVertical: 12, paddingHorizontal: 16 },
  comingSoonCard: { borderRadius: 20, borderWidth: 1, paddingHorizontal: 20, paddingVertical: 28, alignItems: 'center' },
  comingSoonIconWrap: { width: 88, height: 88, borderRadius: 28, alignItems: 'center', justifyContent: 'center', marginBottom: 18 },
  comingSoonTitle: { fontSize: 28, fontWeight: '800', marginBottom: 10, textAlign: 'center' },
  comingSoonCopy: { fontSize: 15, lineHeight: 22, textAlign: 'center', marginBottom: 20 },
  notifyRow: { width: '100%', flexDirection: 'row', flexWrap: 'wrap', alignItems: 'center', marginBottom: 8 },
  notifyInput: { flexGrow: 1, flexBasis: 180, minWidth: 160, borderWidth: 1, borderRadius: 12, paddingHorizontal: 12, paddingVertical: 12, fontSize: 14, marginRight: 8, marginBottom: 8 },
  notifyButton: { borderRadius: 12, paddingHorizontal: 16, paddingVertical: 12, alignItems: 'center', justifyContent: 'center', marginBottom: 8 },
  notifyButtonText: { color: '#ffffff', fontSize: 14, fontWeight: '700' },
  categoryGrid: { width: '100%', flexDirection: 'row', flexWrap: 'wrap', justifyContent: 'space-between', marginTop: 18 },
  categoryCard: { width: '48%', borderRadius: 16, borderWidth: 1, padding: 14, marginBottom: 12 },
  categoryIconWrap: { width: 44, height: 44, borderRadius: 14, alignItems: 'center', justifyContent: 'center', marginBottom: 10 },
  categoryTitle: { fontSize: 15, fontWeight: '800', marginBottom: 6 },
  categoryCopy: { fontSize: 12, lineHeight: 18 },
  emptyTitle: { fontSize: 22, fontWeight: '800', marginTop: 14, textAlign: 'center' },
  emptyCopy: { fontSize: 14, lineHeight: 21, textAlign: 'center', marginTop: 8, maxWidth: 320 },
});

function resolveImageUrl(url?: string | null) {
  if (!url) return undefined;
  if (/^https?:\/\//i.test(url)) return url;
  if (url.startsWith('/')) return `http://localhost/Phambili_Ma_Africa${url}`;
  return `http://localhost/Phambili_Ma_Africa/${url}`;
}

function toCurrency(value: number | string | undefined) {
  const numeric = Number(value ?? 0);
  return `R${numeric.toFixed(2)}`;
}

function getInitials(fullName?: string) {
  if (!fullName) return 'CU';
  return fullName
    .split(' ')
    .map((part) => part[0])
    .join('')
    .slice(0, 2)
    .toUpperCase();
}

function ScreenHeader({ title, showCart = true, onCartPress }: { title?: string; showCart?: boolean; onCartPress?: () => void }) {
  const { colors } = useTheme();
  const totalQuantity = useCartStore((state) => state.totalQuantity);

  return (
    <View style={[styles.headerWrap, { backgroundColor: colors.surface, borderBottomColor: colors.border }]}> 
      <View style={styles.header}>
        <View style={styles.logoWrap}>
          <View style={[styles.logoBadge, { backgroundColor: colors.primary }]}> 
            <Text style={styles.logoBadgeText}>P</Text>
          </View>
          <Text style={[styles.logoText, { color: colors.text }]}>{title || 'Phambili Ma-Africa'}</Text>
        </View>

        {showCart && (
          <TouchableOpacity style={[styles.cartButton, { backgroundColor: colors.backgroundTint, borderColor: colors.border }]} onPress={onCartPress || undefined}> 
            <Ionicons name="cart-outline" size={22} color={colors.text} />
            {totalQuantity > 0 ? (
              <View style={styles.cartBadge}>
                <Text style={styles.cartBadgeText}>{totalQuantity > 99 ? '99+' : totalQuantity}</Text>
              </View>
            ) : null}
          </TouchableOpacity>
        )}
      </View>
    </View>
  );
}

function HomeScreen({ navigation }: any) {
  const { colors } = useTheme();
  const [services, setServices] = useState<ServiceItem[]>([]);
  const [products, setProducts] = useState<ProductItem[]>([]);
  const [loading, setLoading] = useState(true);
  const addItem = useCartStore((state) => state.addItem);

  useEffect(() => {
    const loadData = async () => {
      try {
        const [servicesResponse, productsResponse] = await Promise.all([
          apiClient.get('/services.php', { params: { path: 'public' } }),
          apiClient.get('/products.php', { params: { path: 'public' } }),
        ]);

        setServices(Array.isArray(servicesResponse.data?.services) ? servicesResponse.data.services : []);
        setProducts(Array.isArray(productsResponse.data?.products) ? productsResponse.data.products : []);
      } catch (error) {
        console.error('Error loading home data', error);
      } finally {
        setLoading(false);
      }
    };

    loadData();
  }, []);

  const availableServices = services.filter(isItemAvailable);
  const availableProducts = products.filter(isItemAvailable);

  return (
    <View style={[styles.screen, { backgroundColor: colors.background }]}> 
      <ScreenHeader onCartPress={() => navigation.navigate('Cart')} />
      <ScrollView contentContainerStyle={styles.content}>
        <View style={[styles.hero, { backgroundColor: colors.primary }]}> 
          <Text style={styles.heroTag}>Premium Cleaning</Text>
          <Text style={styles.heroTitle}>Your spotless space starts here.</Text>
          <Text style={styles.heroSubtitle}>Book trusted cleaning services and shop care essentials in minutes.</Text>

          <View style={styles.heroStats}>
            <View style={styles.statCard}>
              <Text style={styles.statValue}>4.9/5</Text>
              <Text style={styles.statLabel}>Client rating</Text>
            </View>
            <View style={styles.statCard}>
              <Text style={styles.statValue}>24/7</Text>
              <Text style={styles.statLabel}>Support</Text>
            </View>
          </View>

          <View style={styles.heroActions}> 
            <TouchableOpacity style={styles.heroButton} onPress={() => navigation.navigate('BookingForm')}>
              <Text style={styles.heroButtonText}>Book Now</Text>
            </TouchableOpacity>
            <TouchableOpacity style={styles.heroLink} onPress={() => navigation.navigate('Services')}>
              <Text style={styles.heroLinkText}>Explore Service</Text>
              <Ionicons name="arrow-forward" size={16} color="#ffffff" />
            </TouchableOpacity>
          </View>
        </View>

        <View style={styles.section}>
          <View style={styles.sectionHeader}>
            <Text style={[styles.sectionTitle, { color: colors.text }]}>Our Services</Text>
            <Text style={[styles.sectionLink, { color: colors.primary }]} onPress={() => navigation.navigate('Services')}>View all</Text>
          </View>

          {loading ? (
            <Text style={[styles.loadingText, { color: colors.textLight }]}>Loading services…</Text>
          ) : availableServices.length === 0 ? (
            <NoServicesState />
          ) : (
            <View style={styles.serviceGrid}>
              {availableServices.slice(0, 4).map((service) => {
                const serviceName = service.Name || service.name || 'Service';
                const serviceDescription = service.Description || service.description || 'Carefully managed by our team.';
                const serviceDuration = service.Duration ?? service.duration ?? 60;
                const serviceCategory = service.Category || service.category || 'Cleaning';
                const serviceImage = resolveImageUrl(service.Image_URL || service.image_url);

                return (
                  <TouchableOpacity
                    key={service.ID ?? service.id ?? serviceName}
                    style={[styles.serviceCard, { backgroundColor: colors.surface, borderColor: colors.border }]}
                    onPress={() => navigation.navigate('BookingForm', { serviceId: Number(service.ID ?? service.id ?? 0) || undefined })}
                  >
                    <View style={[styles.serviceIconWrap, { backgroundColor: serviceCategory.includes('Office') ? '#dcfce7' : serviceCategory.includes('Deep') ? '#fef3c7' : '#dbeafe' }]}> 
                      {serviceImage ? (
                        <Image source={{ uri: serviceImage }} style={{ width: 40, height: 40, borderRadius: 12 }} />
                      ) : (
                        <Ionicons name="sparkles-outline" size={26} color={colors.primary} />
                      )}
                    </View>
                    <Text style={[styles.serviceName, { color: colors.text }]}>{serviceName}</Text>
                    <Text style={[styles.serviceDescription, { color: colors.textLight }]}>{serviceDescription}</Text>
                    <View style={styles.serviceFooter}>
                      <Text style={[styles.servicePrice, { color: colors.primary }]}>{serviceDuration} min</Text>
                      <Ionicons name="arrow-forward" size={16} color={colors.primary} />
                    </View>
                  </TouchableOpacity>
                );
              })}
            </View>
          )}
        </View>

        <View style={styles.section}>
          <View style={styles.sectionHeader}>
            <Text style={[styles.sectionTitle, { color: colors.text }]}>Featured Products</Text>
            <Text style={[styles.sectionLink, { color: colors.primary }]} onPress={() => navigation.navigate('Products')}>Shop</Text>
          </View>

          {loading ? (
            <Text style={[styles.loadingText, { color: colors.textLight }]}>Loading products…</Text>
          ) : availableProducts.length === 0 ? (
            null
          ) : (
            <View style={styles.productGrid}>
              {availableProducts.slice(0, 4).map((product) => {
                const productName = product.Name || product.name || 'Product';
                const productDescription = product.Description || product.description || 'Essential care item';
                const productPrice = Number(product.Price ?? product.price ?? 0);
                const productImage = resolveImageUrl(product.Image_URL || product.image_url);
                const productCategory = product.Category || product.category || 'General';

                return (
                  <TouchableOpacity
                    key={product.ID ?? product.id ?? productName}
                    style={[styles.productCard, { backgroundColor: colors.surface, borderColor: colors.border }]}
                    onPress={() => navigation.navigate('Products')}
                  >
                    <View style={[styles.productImageWrap, { backgroundColor: productCategory.includes('Kitchen') ? '#fef3c7' : productCategory.includes('Laundry') ? '#dbeafe' : '#dcfce7' }]}> 
                      {productImage ? (
                        <Image source={{ uri: productImage }} style={styles.productImage} resizeMode="cover" />
                      ) : (
                        <Text style={styles.productEmoji}>🧼</Text>
                      )}
                    </View>
                    <Text style={[styles.productName, { color: colors.text }]}>{productName}</Text>
                    <Text style={[styles.productMeta, { color: colors.textLight }]}>{productDescription}</Text>
                    <View style={styles.productFooter}>
                      <Text style={[styles.productPrice, { color: colors.primary }]}>{toCurrency(productPrice)}</Text>
                      <TouchableOpacity
                        style={[styles.addButton, { backgroundColor: colors.primary }]}
                        onPress={(event) => {
                          event.stopPropagation();
                          addItem({ id: Number(product.ID ?? product.id ?? 0), name: productName, price: productPrice, image: productImage || null });
                        }}
                      >
                        <Ionicons name="add" size={18} color="#ffffff" />
                      </TouchableOpacity>
                    </View>
                  </TouchableOpacity>
                );
              })}
            </View>
          )}
        </View>
      </ScrollView>
    </View>
  );
}

function BookingsScreen({ navigation }: any) {
  const { colors } = useTheme();
  const [activeTab, setActiveTab] = useState<'upcoming' | 'past'>('upcoming');
  const [bookings, setBookings] = useState<BookingItem[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const loadBookings = async () => {
      try {
        const response = await apiClient.get('/bookings.php', { params: { path: 'my-bookings', page: 1, limit: 50 } });
        const list = Array.isArray(response.data?.bookings) ? response.data.bookings : [];
        setBookings(list);
      } catch (error) {
        console.error('Error loading bookings', error);
      } finally {
        setLoading(false);
      }
    };

    loadBookings();
  }, []);

  const now = new Date();
  const displayBookings = bookings.filter((booking) => {
    const bookingDate = new Date(`${booking.Date ?? booking.date ?? '2099-01-01'}T${booking.Time ?? booking.time ?? '09:00'}`);
    const matchesUpcoming = (booking.Status ?? booking.status ?? '').toLowerCase() !== 'completed' && (booking.Status ?? booking.status ?? '').toLowerCase() !== 'cancelled' && (booking.Status ?? booking.status ?? '').toLowerCase() !== 'rejected';
    return activeTab === 'upcoming' ? matchesUpcoming && bookingDate >= now : !matchesUpcoming || bookingDate < now;
  });

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'requested':
        return '#f59e0b';
      case 'confirmed':
        return '#10b981';
      case 'completed':
        return '#2563eb';
      case 'cancelled':
        return '#ef4444';
      default:
        return '#64748b';
    }
  };

  return (
    <View style={[styles.screen, { backgroundColor: colors.background }]}> 
      <ScreenHeader title="Bookings" onCartPress={() => navigation.navigate('Cart')} />
      <View style={[styles.bookingsContainer, { backgroundColor: colors.background }]}> 
        <View style={[styles.bookingHero, { backgroundColor: colors.backgroundTint, borderColor: colors.border }]}>
          <View style={styles.bookingHeroHeader}>
            <Text style={[styles.bookingHeroTitle, { color: colors.text }]}>Your bookings</Text>
            <TouchableOpacity style={[styles.bookingHeroButton, { backgroundColor: colors.primary }]} onPress={() => navigation.navigate('BookingForm')}>
              <Text style={styles.bookingHeroButtonText}>New</Text>
            </TouchableOpacity>
          </View>
          <View style={styles.featureRow}>
            <View style={[styles.featurePill, { backgroundColor: colors.surface, borderColor: colors.border }]}>
              <Text style={[styles.featurePillText, { color: colors.primary }]}>Trusted team</Text>
            </View>
            <View style={[styles.featurePill, { backgroundColor: colors.surface, borderColor: colors.border }]}>
              <Text style={[styles.featurePillText, { color: colors.primary }]}>Fast quotes</Text>
            </View>
            <View style={[styles.featurePill, { backgroundColor: colors.surface, borderColor: colors.border }]}>
              <Text style={[styles.featurePillText, { color: colors.primary }]}>Flexible plans</Text>
            </View>
          </View>
          <View style={styles.bookingSummary}>
            <View style={[styles.summaryChip, { backgroundColor: colors.surface, borderColor: colors.border }]}>
              <Text style={[styles.summaryChipValue, { color: colors.primary }]}>{bookings.filter((booking) => (booking.Status ?? booking.status ?? '').toLowerCase() !== 'completed' && (booking.Status ?? booking.status ?? '').toLowerCase() !== 'cancelled' && (booking.Status ?? booking.status ?? '').toLowerCase() !== 'rejected').length}</Text>
              <Text style={[styles.summaryChipLabel, { color: colors.textLight }]}>Open</Text>
            </View>
            <View style={[styles.summaryChip, { backgroundColor: colors.surface, borderColor: colors.border }]}>
              <Text style={[styles.summaryChipValue, { color: colors.success }]}>{bookings.filter((booking) => (booking.Status ?? booking.status ?? '').toLowerCase() === 'completed').length}</Text>
              <Text style={[styles.summaryChipLabel, { color: colors.textLight }]}>Done</Text>
            </View>
          </View>
        </View>

        <View style={[styles.tabContainer, { borderBottomColor: colors.border }]}> 
          <TouchableOpacity style={[styles.tab, { borderBottomColor: activeTab === 'upcoming' ? colors.primary : 'transparent' }]} onPress={() => setActiveTab('upcoming')}>
            <Text style={[styles.tabText, { color: activeTab === 'upcoming' ? colors.primary : colors.textLight }]}>Upcoming</Text>
          </TouchableOpacity>
          <TouchableOpacity style={[styles.tab, { borderBottomColor: activeTab === 'past' ? colors.primary : 'transparent' }]} onPress={() => setActiveTab('past')}>
            <Text style={[styles.tabText, { color: activeTab === 'past' ? colors.primary : colors.textLight }]}>Past</Text>
          </TouchableOpacity>
        </View>

        <ScrollView style={styles.bookingsList}>
          {loading ? (
            <Text style={[styles.loadingText, { color: colors.textLight }]}>Loading bookings…</Text>
          ) : displayBookings.length === 0 ? (
            <View style={styles.emptyState}>
              <Ionicons name="calendar-outline" size={64} color={colors.textLight} />
              <Text style={[styles.emptyText, { color: colors.textLight }]}>No bookings made</Text>
              <TouchableOpacity style={[styles.emptyActionButton, { backgroundColor: colors.primary }]} onPress={() => navigation.navigate('BookingForm')}>
                <Text style={[styles.emptyActionText, { color: '#ffffff' }]}>Make a booking</Text>
              </TouchableOpacity>
            </View>
          ) : (
            displayBookings.map((booking) => {
              const status = (booking.Status ?? booking.status ?? 'requested').toLowerCase();
              const serviceName = booking.Service_Name ?? booking.service_name ?? 'Service';
              const bookingDate = booking.Date ?? booking.date ?? 'Not set';
              const bookingTime = booking.Time ?? booking.time ?? 'Not set';
              const total = Number(booking.Quoted_Amount ?? booking.quoted_amount ?? 0);

              return (
                <TouchableOpacity key={booking.ID ?? booking.id ?? serviceName} style={[styles.bookingCard, { backgroundColor: colors.surface, borderColor: colors.border }]}>
                  <View style={styles.bookingHeader}>
                    <Text style={[styles.bookingService, { color: colors.text }]}>{serviceName}</Text>
                    <View style={[styles.statusBadge, { backgroundColor: `${getStatusColor(status)}20` }]}> 
                      <Text style={[styles.statusText, { color: getStatusColor(status) }]}>{status}</Text>
                    </View>
                  </View>
                  <View style={styles.bookingDetails}>
                    <View style={styles.detailItem}>
                      <Ionicons name="calendar" size={16} color={colors.primary} />
                      <Text style={[styles.detailText, { color: colors.textLight }]}>{bookingDate}</Text>
                    </View>
                    <View style={styles.detailItem}>
                      <Ionicons name="time" size={16} color={colors.primary} />
                      <Text style={[styles.detailText, { color: colors.textLight }]}>{bookingTime}</Text>
                    </View>
                    <View style={styles.detailItem}>
                      <Ionicons name="location" size={16} color={colors.primary} />
                      <Text style={[styles.detailText, { color: colors.textLight }]}>{booking.Address ?? booking.address ?? 'Address not set'}</Text>
                    </View>
                  </View>
                  <View style={styles.bookingFooter}>
                    <Text style={[styles.bookingPrice, { color: colors.primary }]}>{total > 0 ? toCurrency(total) : 'Quote pending'}</Text>
                    {status === 'requested' ? (
                      <TouchableOpacity
                        style={[styles.editButton, { borderColor: colors.border, backgroundColor: colors.background }]}
                        onPress={() => navigation.navigate('BookingForm', { editBooking: booking })}
                      >
                        <Text style={[styles.editButtonText, { color: colors.primary }]}>Edit</Text>
                      </TouchableOpacity>
                    ) : (
                      <Ionicons name="chevron-forward" size={20} color={colors.textLight} />
                    )}
                  </View>
                </TouchableOpacity>
              );
            })
          )}
        </ScrollView>
      </View>
    </View>
  );
}

function ServicesScreen({ navigation }: any) {
  const { colors } = useTheme();
  const [services, setServices] = useState<ServiceItem[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const loadServices = async () => {
      try {
        const response = await apiClient.get('/services.php', { params: { path: 'public' } });
        setServices(Array.isArray(response.data?.services) ? response.data.services : []);
      } catch (error) {
        console.error('Error loading services', error);
      } finally {
        setLoading(false);
      }
    };

    loadServices();
  }, []);

  const availableServices = services.filter(isItemAvailable);

  return (
    <View style={[styles.screen, { backgroundColor: colors.background }]}> 
      <ScreenHeader title="Services" onCartPress={() => navigation.navigate('Cart')} />
      <ScrollView contentContainerStyle={styles.content}>
        {loading ? (
          <Text style={[styles.loadingText, { color: colors.textLight }]}>Loading services…</Text>
        ) : availableServices.length === 0 ? (
          <NoServicesState />
        ) : (
          <View>
            {availableServices.map((service) => {
              const image = resolveImageUrl(service.Image_URL || service.image_url);
              const category = service.Category || service.category || 'Cleaning';
              const description = service.Description || service.description || 'Professional cleaning service';
              const duration = service.Duration ?? service.duration ?? 60;
              const name = service.Name || service.name || 'Service';

              return (
                <View key={service.ID ?? service.id ?? name} style={[styles.listSection, { backgroundColor: colors.surface, borderColor: colors.border }]}> 
                  <View style={styles.listHeader}>
                    <Text style={[styles.listHeading, { color: colors.text }]}>{name}</Text>
                    <Text style={[styles.listPrice, { color: colors.primary }]}>{duration} min</Text>
                  </View>
                  {image ? <Image source={{ uri: image }} style={{ width: '100%', height: 170, borderRadius: 14, marginBottom: 10 }} resizeMode="cover" /> : null}
                  <Text style={[styles.helperText, { color: colors.textLight }]}>{category}</Text>
                  <Text style={[styles.listDescription, { color: colors.textLight }]}>{description}</Text>
                  <TouchableOpacity style={[styles.submitButton, { backgroundColor: colors.primary }]} onPress={() => navigation.navigate('BookingForm', { serviceId: Number(service.ID ?? service.id ?? 0) || undefined })}>
                    <Text style={styles.submitText}>Request a quote</Text>
                  </TouchableOpacity>
                </View>
              );
            })}
          </View>
        )}
      </ScrollView>
    </View>
  );
}

function ProductsScreen({ navigation }: any) {
  const { colors } = useTheme();
  const [products, setProducts] = useState<ProductItem[]>([]);
  const [loading, setLoading] = useState(true);
  const addItem = useCartStore((state) => state.addItem);

  useEffect(() => {
    const loadProducts = async () => {
      try {
        const response = await apiClient.get('/products.php', { params: { path: 'public' } });
        setProducts(Array.isArray(response.data?.products) ? response.data.products : []);
      } catch (error) {
        console.error('Error loading products', error);
      } finally {
        setLoading(false);
      }
    };

    loadProducts();
  }, []);

  const availableProducts = products.filter(isItemAvailable);

  return (
    <View style={[styles.screen, { backgroundColor: colors.background }]}> 
      <ScreenHeader title="Products" onCartPress={() => navigation.navigate('Cart')} />
      <ScrollView contentContainerStyle={styles.content}>
        {loading ? (
          <Text style={[styles.loadingText, { color: colors.textLight }]}>Loading products…</Text>
        ) : availableProducts.length === 0 ? (
          <ComingSoonSection />
        ) : (
          <View>
            {availableProducts.map((product) => {
              const image = resolveImageUrl(product.Image_URL || product.image_url);
              const name = product.Name || product.name || 'Product';
              const description = product.Description || product.description || 'Cleaning essentials';
              const price = Number(product.Price ?? product.price ?? 0);

              return (
                <View key={product.ID ?? product.id ?? name} style={[styles.listSection, { backgroundColor: colors.surface, borderColor: colors.border }]}> 
                  {image ? <Image source={{ uri: image }} style={{ width: '100%', height: 170, borderRadius: 14, marginBottom: 12 }} resizeMode="cover" /> : null}
                  <View style={styles.listHeader}>
                    <Text style={[styles.listHeading, { color: colors.text }]}>{name}</Text>
                    <Text style={[styles.listPrice, { color: colors.primary }]}>{toCurrency(price)}</Text>
                  </View>
                  <Text style={[styles.listDescription, { color: colors.textLight }]}>{description}</Text>
                  <TouchableOpacity
                    style={[styles.secondaryButton, { backgroundColor: colors.backgroundTint, borderColor: colors.border }]}
                    onPress={() => addItem({ id: Number(product.ID ?? product.id ?? 0), name, price, image: image || null })}
                  >
                    <Text style={{ color: colors.primary, fontWeight: '700' }}>Add to cart</Text>
                  </TouchableOpacity>
                </View>
              );
            })}
          </View>
        )}
      </ScrollView>
    </View>
  );
}

function CartScreen({ navigation }: any) {
  const { colors } = useTheme();
  const items = useCartStore((state) => state.items);
  const updateQuantity = useCartStore((state) => state.updateQuantity);
  const removeItem = useCartStore((state) => state.removeItem);
  const clearCart = useCartStore((state) => state.clearCart);

  const totals = items.reduce(
    (sum, item) => ({
      quantity: sum.quantity + item.quantity,
      amount: sum.amount + item.quantity * Number(item.price ?? 0),
    }),
    { quantity: 0, amount: 0 }
  );

  return (
    <View style={[styles.screen, { backgroundColor: colors.background }]}> 
      <ScreenHeader title="Cart" showCart={false} />
      <ScrollView contentContainerStyle={styles.cartList}>
        {items.length === 0 ? (
          <View style={styles.emptyState}>
            <Ionicons name="cart-outline" size={64} color={colors.textLight} />
            <Text style={[styles.emptyText, { color: colors.textLight }]}>Your cart is empty</Text>
            <TouchableOpacity style={[styles.emptyActionButton, { backgroundColor: colors.primary }]} onPress={() => navigation.navigate('Products')}>
              <Text style={[styles.emptyActionText, { color: '#ffffff' }]}>Continue shopping</Text>
            </TouchableOpacity>
          </View>
        ) : (
          <>
            {items.map((item) => (
              <View key={item.id} style={[styles.cartItem, { backgroundColor: colors.surface, borderColor: colors.border }]}> 
                {item.image ? <Image source={{ uri: item.image }} style={styles.cartItemImage} resizeMode="cover" /> : <View style={[styles.cartItemImage, { backgroundColor: colors.backgroundTint, alignItems: 'center', justifyContent: 'center' }]}><Ionicons name="bag-outline" size={28} color={colors.primary} /></View>}
                <View style={styles.cartItemMeta}>
                  <Text style={[styles.listHeading, { color: colors.text }]}>{item.name}</Text>
                  <Text style={[styles.listPrice, { color: colors.primary }]}>{toCurrency(item.price)}</Text>
                  <View style={styles.quantityRow}>
                    <TouchableOpacity style={[styles.quantityButton, { backgroundColor: colors.background, borderColor: colors.border }]} onPress={() => updateQuantity(item.id, item.quantity - 1)}>
                      <Ionicons name="remove" size={16} color={colors.text} />
                    </TouchableOpacity>
                    <Text style={[styles.quantityValue, { color: colors.text }]}>{item.quantity}</Text>
                    <TouchableOpacity style={[styles.quantityButton, { backgroundColor: colors.primary, borderColor: colors.primary }]} onPress={() => updateQuantity(item.id, item.quantity + 1)}>
                      <Ionicons name="add" size={16} color="#ffffff" />
                    </TouchableOpacity>
                  </View>
                </View>
                <TouchableOpacity onPress={() => removeItem(item.id)}>
                  <Ionicons name="trash-outline" size={20} color={colors.danger || '#ef4444'} />
                </TouchableOpacity>
              </View>
            ))}

            <View style={[styles.cartSummary, { backgroundColor: colors.surface, borderColor: colors.border }]}> 
              <View style={styles.cartSummaryRow}>
                <Text style={[styles.infoLabel, { color: colors.textLight }]}>Items</Text>
                <Text style={[styles.infoValue, { color: colors.text }]}>{totals.quantity}</Text>
              </View>
              <View style={styles.cartSummaryRow}>
                <Text style={[styles.infoLabel, { color: colors.textLight }]}>Total</Text>
                <Text style={[styles.listPrice, { color: colors.primary }]}>{toCurrency(totals.amount)}</Text>
              </View>
              <TouchableOpacity style={[styles.submitButton, { backgroundColor: colors.primary, marginTop: 12 }]} onPress={() => navigation.navigate('BookingForm')}>
                <Text style={styles.submitText}>Proceed to booking</Text>
              </TouchableOpacity>
              <TouchableOpacity style={[styles.secondaryButton, { backgroundColor: colors.backgroundTint, borderColor: colors.border, marginTop: 12 }]} onPress={clearCart}>
                <Text style={{ color: colors.primary, fontWeight: '700' }}>Clear cart</Text>
              </TouchableOpacity>
            </View>
          </>
        )}
      </ScrollView>
    </View>
  );
}

function BookingFormScreen({ navigation, route }: any) {
  const { colors } = useTheme();
  const [services, setServices] = useState<ServiceItem[]>([]);
  const [servicePickerOpen, setServicePickerOpen] = useState(false);
  const [form, setForm] = useState({
    serviceId: route?.params?.serviceId ? String(route.params.serviceId) : '',
    propertyType: 'house',
    cleaningFrequency: 'once',
    date: '',
    time: '09:00',
    addressStreet: '',
    addressCity: '',
    addressState: '',
    addressPostal: '',
    specialInstructions: '',
    customerName: '',
    customerEmail: '',
    customerPhone: '',
  });
  const [loading, setLoading] = useState(true);
  const [submitMessage, setSubmitMessage] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [isEditingBooking, setIsEditingBooking] = useState(false);
  const [editingBookingId, setEditingBookingId] = useState<string | number | null>(null);
  const [calendarVisible, setCalendarVisible] = useState(false);
  const minimumDate = new Date();
  minimumDate.setDate(minimumDate.getDate() + 1);

  useEffect(() => {
    const loadDefaults = async () => {
      try {
        const serviceResponse = await apiClient.get('/services.php', { params: { path: 'public' } });
        const profileResponse = await apiClient.get('/profile.php');

        const loadedServices = (Array.isArray(serviceResponse.data?.services) ? serviceResponse.data.services : []).filter(isItemAvailable);
        setServices(loadedServices);

        if (route?.params?.serviceId && !loadedServices.some((service: ServiceItem) => String(service.ID ?? service.id ?? '') === String(route.params.serviceId))) {
          setForm((current) => ({ ...current, serviceId: '' }));
        }

        // If opened for editing an existing booking, prefill form values
        if (route?.params?.editBooking) {
          const b = route.params.editBooking as any;
          setIsEditingBooking(true);
          setEditingBookingId(b.ID ?? b.id ?? null);

          // try to resolve service id by explicit id or by matching name
          let svcId = '';
          if (b.Service_ID || b.ServiceId || b.service_id) svcId = String(b.Service_ID ?? b.ServiceId ?? b.service_id);
          else if (b.Service_Name || b.service_name) {
            const match = loadedServices.find((s: ServiceItem) => (s.Name || s.name || '').toLowerCase() === (b.Service_Name || b.service_name || '').toLowerCase());
            if (match) svcId = String(match.ID ?? match.id ?? '');
          }

          setForm((current) => ({
            ...current,
            serviceId: svcId,
            propertyType: b.Property_Type ?? b.property_type ?? current.propertyType,
            cleaningFrequency: b.Cleaning_Frequency ?? b.cleaning_frequency ?? current.cleaningFrequency,
            date: b.Date ?? b.date ?? current.date,
            time: b.Time ?? b.time ?? current.time,
            addressStreet: b.Address_Street ?? b.address_street ?? b.Address ?? current.addressStreet,
            addressCity: b.Address_City ?? b.address_city ?? current.addressCity,
            addressState: b.Address_State ?? b.address_state ?? current.addressState,
            addressPostal: b.Address_Postal_Code ?? b.address_postal ?? b.Postal ?? current.addressPostal,
            specialInstructions: b.Special_Instructions ?? b.special_instructions ?? current.specialInstructions,
          }));
        }

        if (profileResponse.data?.success && profileResponse.data?.customer) {
          const customer = profileResponse.data.customer;
          const address = profileResponse.data.address || {};
          setForm((current) => ({
            ...current,
            customerName: customer.Full_Name || customer.full_name || '',
            customerEmail: customer.Email || customer.email || '',
            customerPhone: customer.Phone || customer.phone || '',
            addressStreet: address.street || '',
            addressCity: address.city || '',
            addressState: address.state || '',
            addressPostal: address.postal || '',
          }));
        }
      } catch (error) {
        console.error('Error loading booking defaults', error);
      } finally {
        setLoading(false);
      }
    };

    loadDefaults();
  }, [route?.params?.serviceId]);

  const availableServices = services.filter(isItemAvailable);
  const selectedService = availableServices.find((service) => String(service.ID ?? service.id ?? '') === form.serviceId) || null;

  const updateForm = (key: string, value: string) => setForm((current) => ({ ...current, [key]: value }));

  const handleSubmit = async () => {
    setSubmitMessage('');

    if (!form.serviceId || !form.date || !form.addressStreet || !form.addressCity || !form.addressState || !form.addressPostal) {
      setSubmitMessage('Please complete all required fields.');
      return;
    }

    setIsSubmitting(true);

    try {
      if (isEditingBooking && editingBookingId) {
        const response = await apiClient.post('/bookings.php?path=update', {
          Booking_ID: editingBookingId,
          Service_ID: Number(form.serviceId),
          Date: form.date,
          Time: form.time,
          Property_Type: form.propertyType,
          Cleaning_Frequency: form.cleaningFrequency,
          Address_Street: form.addressStreet,
          Address_City: form.addressCity,
          Address_State: form.addressState,
          Address_Postal_Code: form.addressPostal,
          Special_Instructions: form.specialInstructions,
        });

        if (response.data?.success) {
          setSubmitMessage('Booking updated successfully.');
          setTimeout(() => navigation.navigate('Bookings'), 800);
        } else {
          setSubmitMessage(response.data?.message || 'Unable to update booking');
        }
      } else {
        const response = await apiClient.post('/bookings.php?path=create', {
          Service_ID: Number(form.serviceId),
          Date: form.date,
          Time: form.time,
          Property_Type: form.propertyType,
          Cleaning_Frequency: form.cleaningFrequency,
          Address_Street: form.addressStreet,
          Address_City: form.addressCity,
          Address_State: form.addressState,
          Address_Postal_Code: form.addressPostal,
          Special_Instructions: form.specialInstructions,
        });

        if (response.data?.success) {
          setSubmitMessage('Booking request submitted successfully.');
          setForm((current) => ({ ...current, serviceId: '', date: '', time: '09:00', addressStreet: '', addressCity: '', addressState: '', addressPostal: '', specialInstructions: '', propertyType: 'house', cleaningFrequency: 'once' }));
          setTimeout(() => navigation.navigate('Bookings'), 800);
        } else {
          setSubmitMessage(response.data?.message || 'Unable to submit booking');
        }
      }
    } catch (error: any) {
      setSubmitMessage(error?.response?.data?.message || error?.message || 'Network error while submitting booking');
    } finally {
      setIsSubmitting(false);
    }
  };

  const PROPERTY_TYPE_OPTIONS = [
    { value: 'house', label: 'House' },
    { value: 'apartment', label: 'Apartment' },
    { value: 'office', label: 'Office' },
    { value: 'commercial', label: 'Commercial Space' },
  ];

  const CLEANING_FREQUENCY_OPTIONS = [
    { value: 'once', label: 'One-time' },
    { value: 'weekly', label: 'Weekly' },
    { value: 'biweekly', label: 'Bi-weekly' },
    { value: 'monthly', label: 'Monthly' },
  ];

  const TIME_OPTIONS = ['08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00'];

  const getUpcomingDateOptions = () => {
    const options: string[] = [];
    const today = new Date();

    for (let day = 1; day <= 14; day += 1) {
      const date = new Date(today);
      date.setDate(today.getDate() + day);
      options.push(date.toISOString().split('T')[0]);
    }

    return options;
  };

  const DATE_OPTIONS = getUpcomingDateOptions();

  const selectedPropertyType = PROPERTY_TYPE_OPTIONS.find((option) => option.value === form.propertyType) ?? null;
  const selectedFrequency = CLEANING_FREQUENCY_OPTIONS.find((option) => option.value === form.cleaningFrequency) ?? null;
  const selectedDate = form.date ? DATE_OPTIONS.find((option) => option === form.date) ?? form.date : null;
  const selectedTime = TIME_OPTIONS.includes(form.time) ? form.time : null;

  const [propertyTypePickerOpen, setPropertyTypePickerOpen] = useState(false);
  const [frequencyPickerOpen, setFrequencyPickerOpen] = useState(false);
  const [timePickerOpen, setTimePickerOpen] = useState(false);

  const renderOptionPicker = (label: string, value: string, options: { value: string; label: string }[], open: boolean, setOpen: (value: boolean) => void, onSelect: (value: string) => void) => (
    <View style={[styles.formCard, { backgroundColor: colors.surface, borderColor: colors.border }]}> 
      <Text style={[styles.fieldLabel, { color: colors.text }]}>{label}</Text>
      <TouchableOpacity
        style={[styles.pickerTrigger, { borderColor: colors.border, backgroundColor: colors.background }]}
        onPress={() => setOpen(!open)}
      >
        <Text style={{ color: value ? colors.text : colors.textLight, fontSize: 14 }}>
          {options.find((option) => option.value === value)?.label || 'Select an option'}
        </Text>
        <Ionicons name={open ? 'chevron-up' : 'chevron-down'} size={18} color={colors.textLight} />
      </TouchableOpacity>

      {open && (
        <View style={[styles.pickerMenu, { borderColor: colors.border, backgroundColor: colors.background }]}> 
          {options.map((option) => {
            const active = option.value === value;
            return (
              <TouchableOpacity
                key={option.value}
                style={[styles.pickerOption, { borderBottomColor: colors.border, backgroundColor: active ? colors.backgroundTint : 'transparent' }]}
                onPress={() => {
                  onSelect(option.value);
                  setOpen(false);
                }}
              >
                <Text style={{ color: colors.text, fontWeight: active ? '700' : '600' }}>{option.label}</Text>
              </TouchableOpacity>
            );
          })}
        </View>
      )}
    </View>
  );

  const renderServicePicker = () => {
    // If user came from a service page (preselected), show a read-only card
    if (form.serviceId && selectedService) {
      const serviceName = selectedService.Name || selectedService.name || 'Service';
      const duration = selectedService.Duration ?? selectedService.duration ?? 60;
      const image = resolveImageUrl(selectedService.Image_URL || selectedService.image_url);

      return (
        <View style={[styles.formCard, { backgroundColor: colors.surface, borderColor: colors.border, marginBottom: 12 }]}> 
          <Text style={[styles.fieldLabel, { color: colors.text }]}>Service</Text>
          <View style={{ flexDirection: 'row', alignItems: 'center', gap: 12 }}>
            {image ? <Image source={{ uri: image }} style={{ width: 64, height: 64, borderRadius: 12 }} /> : <View style={[styles.serviceIconWrap, { backgroundColor: '#dbeafe' }]}><Ionicons name="sparkles-outline" size={28} color={colors.primary} /></View>}
            <View style={{ flex: 1 }}>
              <Text style={{ fontWeight: '800', color: colors.text, fontSize: 16 }}>{serviceName}</Text>
              <Text style={{ color: colors.textLight, marginTop: 4 }}>{duration} min</Text>
            </View>
          </View>
        </View>
      );
    }

    // Otherwise show a picker to choose a service (name + duration)
    return (
      <View style={[styles.formCard, { backgroundColor: colors.surface, borderColor: colors.border, marginBottom: 12 }]}> 
        <Text style={[styles.fieldLabel, { color: colors.text }]}>Service *</Text>
        <TouchableOpacity
          style={[styles.pickerTrigger, { borderColor: colors.border, backgroundColor: colors.background }]}
          onPress={() => setServicePickerOpen((v) => !v)}
        >
          <Text style={{ color: form.serviceId ? colors.text : colors.textLight, fontSize: 14 }}>
            {selectedService ? `${selectedService.Name || selectedService.name} (${selectedService.Duration ?? selectedService.duration ?? 60} min)` : 'Choose a service'}
          </Text>
          <Ionicons name={servicePickerOpen ? 'chevron-up' : 'chevron-down'} size={18} color={colors.textLight} />
        </TouchableOpacity>

        {servicePickerOpen && (
          <View style={[styles.pickerMenu, { borderColor: colors.border, backgroundColor: colors.background }]}> 
            {availableServices.map((svc) => {
              const id = String(svc.ID ?? svc.id ?? '');
              const label = `${svc.Name || svc.name || 'Service'} (${svc.Duration ?? svc.duration ?? 60} min)`;
              const active = form.serviceId === id;
              return (
                <TouchableOpacity
                  key={id || label}
                  style={[styles.pickerOption, { borderBottomColor: colors.border, backgroundColor: active ? colors.backgroundTint : 'transparent' }]}
                  onPress={() => {
                    updateForm('serviceId', id);
                    setServicePickerOpen(false);
                  }}
                >
                  <Text style={{ color: colors.text, fontWeight: active ? '700' : '600' }}>{label}</Text>
                </TouchableOpacity>
              );
            })}
          </View>
        )}
      </View>
    );
  };

  const renderDatePicker = () => (
    <View style={[styles.formCard, { backgroundColor: colors.surface, borderColor: colors.border }]}> 
      <Text style={[styles.fieldLabel, { color: colors.text }]}>Preferred Date *</Text>
      <TouchableOpacity
        style={[styles.pickerTrigger, { borderColor: colors.border, backgroundColor: colors.background }]}
        onPress={() => setCalendarVisible((value) => !value)}
      >
        <Text style={{ color: form.date ? colors.text : colors.textLight, fontSize: 14 }}>
          {form.date ? new Date(form.date).toLocaleDateString('en-ZA', { day: '2-digit', month: 'short', year: 'numeric' }) : 'Select a date'}
        </Text>
        <Ionicons name="calendar-outline" size={18} color={colors.primary} />
      </TouchableOpacity>

      {calendarVisible ? (
        <View style={[styles.pickerMenu, { borderColor: colors.border, backgroundColor: colors.background, padding: 12 }]}> 
          <Calendar
            minDate={minimumDate.toISOString().split('T')[0]}
            maxDate={new Date(Date.now() + 120 * 24 * 60 * 60 * 1000).toISOString().split('T')[0]}
            onDayPress={(day) => {
              updateForm('date', day.dateString);
              setCalendarVisible(false);
            }}
            markedDates={
              form.date
                ? {
                    [form.date]: {
                      selected: true,
                      selectedColor: colors.primary,
                      selectedTextColor: '#ffffff',
                    },
                  }
                : {}
            }
            theme={{
              backgroundColor: colors.background,
              calendarBackground: colors.background,
              textSectionTitleColor: colors.text,
              selectedDayBackgroundColor: colors.primary,
              selectedDayTextColor: '#ffffff',
              todayTextColor: colors.primary,
              dayTextColor: colors.text,
              textDisabledColor: colors.textMuted,
              monthTextColor: colors.text,
              arrowColor: colors.primary,
              indicatorColor: colors.primary,
              dotColor: colors.primary,
              textDayFontWeight: '500',
              textMonthFontWeight: '700',
              textDayHeaderFontWeight: '600',
            }}
          />
        </View>
      ) : null}
    </View>
  );

  const renderTimePicker = () => (
    <View style={[styles.formCard, { backgroundColor: colors.surface, borderColor: colors.border }]}> 
      <Text style={[styles.fieldLabel, { color: colors.text }]}>Preferred Time *</Text>
      <TouchableOpacity
        style={[styles.pickerTrigger, { borderColor: colors.border, backgroundColor: colors.background }]}
        onPress={() => setTimePickerOpen((value) => !value)}
      >
        <Text style={{ color: selectedTime ? colors.text : colors.textLight, fontSize: 14 }}>
          {selectedTime ? `${selectedTime} ${Number(selectedTime.split(':')[0]) >= 12 ? 'PM' : 'AM'}` : 'Select a time'}
        </Text>
        <Ionicons name={timePickerOpen ? 'chevron-up' : 'chevron-down'} size={18} color={colors.textLight} />
      </TouchableOpacity>

      {timePickerOpen ? (
        <View style={[styles.pickerMenu, { borderColor: colors.border, backgroundColor: colors.background }]}> 
          {TIME_OPTIONS.map((option) => {
            const active = option === form.time;
            return (
              <TouchableOpacity
                key={option}
                style={[styles.pickerOption, { borderBottomColor: colors.border, backgroundColor: active ? colors.backgroundTint : 'transparent' }]}
                onPress={() => {
                  updateForm('time', option);
                  setTimePickerOpen(false);
                }}
              >
                <Text style={{ color: colors.text, fontWeight: active ? '700' : '600' }}>{option}</Text>
              </TouchableOpacity>
            );
          })}
        </View>
      ) : null}
    </View>
  );

  const renderPropertyType = () => renderOptionPicker('Property Type *', form.propertyType, PROPERTY_TYPE_OPTIONS, propertyTypePickerOpen, setPropertyTypePickerOpen, (value) => updateForm('propertyType', value));
  const renderFrequency = () => renderOptionPicker('Service Frequency', form.cleaningFrequency, CLEANING_FREQUENCY_OPTIONS, frequencyPickerOpen, setFrequencyPickerOpen, (value) => updateForm('cleaningFrequency', value));

  const propertyTypeField = renderPropertyType();
  const frequencyField = renderFrequency();
  const dateField = renderDatePicker();
  const timeField = renderTimePicker();

  if (loading) {
    return (
      <View style={[styles.screen, { backgroundColor: colors.background, justifyContent: 'center' }]}> 
        <Text style={[styles.loadingText, { color: colors.textLight }]}>Loading booking form…</Text>
      </View>
    );
  }

  return (
    <View style={[styles.screen, { backgroundColor: colors.background }]}> 
      <ScreenHeader title="Book Service" onCartPress={() => navigation.navigate('Cart')} />
      <ScrollView contentContainerStyle={styles.formWrapper}>
        <Text style={[styles.formHeader, { color: colors.text }]}>Book Your Professional Cleaning Service</Text>
        <Text style={[styles.formSubText, { color: colors.textLight }]}>Fill in the details below and our team will contact you soon.</Text>

        {renderServicePicker()}
        {propertyTypeField}
        {frequencyField}
        {dateField}
        {timeField}

        <View style={[styles.formCard, { backgroundColor: colors.surface, borderColor: colors.border }]}> 
          <Text style={[styles.fieldLabel, { color: colors.text }]}>Street Address *</Text>
          <TextInput value={form.addressStreet} onChangeText={(value) => updateForm('addressStreet', value)} style={[styles.fieldInput, { borderColor: colors.border, color: colors.text, backgroundColor: colors.background }]} placeholder="Street address" placeholderTextColor={colors.textMuted} />

          <Text style={[styles.fieldLabel, { color: colors.text, marginTop: 12 }]}>City *</Text>
          <TextInput value={form.addressCity} onChangeText={(value) => updateForm('addressCity', value)} style={[styles.fieldInput, { borderColor: colors.border, color: colors.text, backgroundColor: colors.background }]} placeholder="City" placeholderTextColor={colors.textMuted} />

          <Text style={[styles.fieldLabel, { color: colors.text, marginTop: 12 }]}>Province *</Text>
          <TextInput value={form.addressState} onChangeText={(value) => updateForm('addressState', value)} style={[styles.fieldInput, { borderColor: colors.border, color: colors.text, backgroundColor: colors.background }]} placeholder="Province" placeholderTextColor={colors.textMuted} />

          <Text style={[styles.fieldLabel, { color: colors.text, marginTop: 12 }]}>Postal Code *</Text>
          <TextInput value={form.addressPostal} onChangeText={(value) => updateForm('addressPostal', value)} style={[styles.fieldInput, { borderColor: colors.border, color: colors.text, backgroundColor: colors.background }]} placeholder="Postal code" placeholderTextColor={colors.textMuted} />

          <Text style={[styles.fieldLabel, { color: colors.text, marginTop: 12 }]}>Special Instructions</Text>
          <TextInput value={form.specialInstructions} onChangeText={(value) => updateForm('specialInstructions', value)} multiline style={[styles.fieldInput, { borderColor: colors.border, color: colors.text, minHeight: 90, textAlignVertical: 'top', backgroundColor: colors.background }]} placeholder="Tell us about your space or cleaning requirements" placeholderTextColor={colors.textMuted} />
        </View>

        <View style={[styles.formCard, { backgroundColor: colors.surface, borderColor: colors.border }]}> 
          <Text style={[styles.fieldLabel, { color: colors.text }]}>Full Name</Text>
          <TextInput value={form.customerName} onChangeText={(value) => updateForm('customerName', value)} style={[styles.fieldInput, { borderColor: colors.border, color: colors.text, backgroundColor: colors.background }]} placeholder="Full name" placeholderTextColor={colors.textMuted} />

          <Text style={[styles.fieldLabel, { color: colors.text, marginTop: 12 }]}>Email</Text>
          <TextInput value={form.customerEmail} onChangeText={(value) => updateForm('customerEmail', value)} style={[styles.fieldInput, { borderColor: colors.border, color: colors.text, backgroundColor: colors.background }]} placeholder="Email" placeholderTextColor={colors.textMuted} />

          <Text style={[styles.fieldLabel, { color: colors.text, marginTop: 12 }]}>Phone</Text>
          <TextInput value={form.customerPhone} onChangeText={(value) => updateForm('customerPhone', value)} style={[styles.fieldInput, { borderColor: colors.border, color: colors.text, backgroundColor: colors.background }]} placeholder="Phone" placeholderTextColor={colors.textMuted} />
        </View>

        {submitMessage ? <Text style={{ color: colors.primary, marginBottom: 12 }}>{submitMessage}</Text> : null}

        <TouchableOpacity style={[styles.submitButton, { backgroundColor: colors.primary }]} onPress={handleSubmit} disabled={isSubmitting}>
          <Text style={styles.submitText}>{isSubmitting ? 'Submitting...' : 'Submit Booking Request'}</Text>
        </TouchableOpacity>
      </ScrollView>
    </View>
  );
}

function ProfileScreen({ navigation }: any) {
  const { colors } = useTheme();
  const logout = useAuthStore((state) => state.logout);
  const [profile, setProfile] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [profileForm, setProfileForm] = useState({ fullname: '', email: '', phone: '' });
  const [addressForm, setAddressForm] = useState({ address_street: '', address_city: '', address_state: '', address_zip: '' });
  const [passwordForm, setPasswordForm] = useState({ current_password: '', new_password: '', confirm_password: '' });
  const [message, setMessage] = useState('');
  const [editing, setEditing] = useState({ profile: false, address: false, password: false });

  const toggleEdit = (section: 'profile' | 'address' | 'password') => {
    setEditing((current) => ({ ...current, [section]: !current[section] }));
    if (message) setMessage('');
  };

  const loadProfile = async () => {
    try {
      const response = await apiClient.get('/profile.php');
      if (response.data?.success) {
        const customer = response.data.customer || {};
        const address = response.data.address || {};
        setProfile(response.data);
        setProfileForm({
          fullname: customer.Full_Name || customer.full_name || '',
          email: customer.Email || customer.email || '',
          phone: customer.Phone || customer.phone || '',
        });
        setAddressForm({
          address_street: address.street || '',
          address_city: address.city || '',
          address_state: address.state || '',
          address_zip: address.postal || '',
        });
      }
    } catch (error) {
      console.error('Error loading profile', error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadProfile();
  }, []);

  const customer = profile?.customer || {};
  const fullName = customer.Full_Name || customer.full_name || 'Customer';
  const email = customer.Email || customer.email || 'customer@example.com';

  const handleUpdateProfile = async () => {
    setMessage('');
    try {
      const response = await apiClient.post('/profile.php', {
        action: 'update_profile',
        fullname: profileForm.fullname,
        email: profileForm.email,
        phone: profileForm.phone,
      });
      setMessage(response.data?.message || 'Profile updated');
      if (response.data?.success) await loadProfile();
    } catch (error: any) {
      setMessage(error?.response?.data?.message || 'Unable to update profile');
    }
  };

  const handleUpdateAddress = async () => {
    setMessage('');
    try {
      const response = await apiClient.post('/profile.php', {
        action: 'update_address',
        address_street: addressForm.address_street,
        address_city: addressForm.address_city,
        address_state: addressForm.address_state,
        address_zip: addressForm.address_zip,
      });
      setMessage(response.data?.message || 'Address updated');
      if (response.data?.success) await loadProfile();
    } catch (error: any) {
      setMessage(error?.response?.data?.message || 'Unable to update address');
    }
  };

  const handleChangePassword = async () => {
    setMessage('');
    try {
      const response = await apiClient.post('/profile.php', {
        action: 'change_password',
        current_password: passwordForm.current_password,
        new_password: passwordForm.new_password,
        confirm_password: passwordForm.confirm_password,
      });
      setMessage(response.data?.message || 'Password updated');
      if (response.data?.success) {
        setPasswordForm({ current_password: '', new_password: '', confirm_password: '' });
      }
    } catch (error: any) {
      setMessage(error?.response?.data?.message || 'Unable to change password');
    }
  };

  const handleLogout = async () => {
    try {
      await apiClient.post('/auth.php', { action: 'logout' });
    } catch (error) {
      console.error('Logout request failed', error);
    } finally {
      await logout();
    }
  };

  return (
    <View style={[styles.profileScreen, { backgroundColor: colors.background }]}> 
      <ScreenHeader title="Profile" onCartPress={() => navigation.navigate('Cart')} />
      <ScrollView style={styles.profileWrap}>
        <View style={[styles.profileCard, { backgroundColor: colors.surface, borderColor: colors.border }]}> 
          <View style={[styles.avatar, { backgroundColor: colors.primary }]}> 
            <Text style={styles.avatarText}>{getInitials(fullName)}</Text>
          </View>
          <View>
            <Text style={[styles.profileName, { color: colors.text }]}>{loading ? 'Loading...' : fullName}</Text>
            <Text style={[styles.profileEmail, { color: colors.textLight }]}>{loading ? 'Please wait' : email}</Text>
          </View>
        </View>

        {message ? <Text style={{ color: colors.primary, marginBottom: 12 }}>{message}</Text> : null}

        <View style={[styles.infoSection, { backgroundColor: colors.surface, borderColor: colors.border }]}> 
          <View style={styles.sectionRow}>
            <Text style={[styles.sectionHeading, { color: colors.text }]}>Personal Information</Text>
            <TouchableOpacity style={[styles.editButton, { borderColor: colors.border, backgroundColor: colors.background }]} onPress={() => toggleEdit('profile')}>
              <Text style={[styles.editButtonText, { color: colors.primary }]}>{editing.profile ? 'Save' : 'Edit'}</Text>
            </TouchableOpacity>
          </View>
          {editing.profile ? (
            <>
              <Text style={[styles.fieldLabel, { color: colors.text, marginLeft: 6 }]}>Full Name</Text>
              <TextInput value={profileForm.fullname} onChangeText={(value) => setProfileForm((current) => ({ ...current, fullname: value }))} style={[styles.fieldInput, { borderColor: colors.border, color: colors.text, backgroundColor: colors.background }]} />
              <Text style={[styles.fieldLabel, { color: colors.text, marginLeft: 6, marginTop: 12 }]}>Email</Text>
              <TextInput value={profileForm.email} onChangeText={(value) => setProfileForm((current) => ({ ...current, email: value }))} style={[styles.fieldInput, { borderColor: colors.border, color: colors.text, backgroundColor: colors.background }]} />
              <Text style={[styles.fieldLabel, { color: colors.text, marginLeft: 6, marginTop: 12 }]}>Phone</Text>
              <TextInput value={profileForm.phone} onChangeText={(value) => setProfileForm((current) => ({ ...current, phone: value }))} style={[styles.fieldInput, { borderColor: colors.border, color: colors.text, backgroundColor: colors.background }]} />
              <TouchableOpacity style={[styles.submitButton, { backgroundColor: colors.primary }]} onPress={handleUpdateProfile}>
                <Text style={styles.submitText}>Save Personal Information</Text>
              </TouchableOpacity>
            </>
          ) : (
            <>
              <Text style={[styles.fieldLabel, { color: colors.text, marginLeft: 6 }]}>Full Name</Text>
              <Text style={[styles.fieldInput, { borderColor: colors.border, color: colors.text, backgroundColor: colors.background, paddingVertical: 12, paddingHorizontal: 12, borderWidth: 1, borderRadius: 12 }]}>{profileForm.fullname || 'Not set'}</Text>
              <Text style={[styles.fieldLabel, { color: colors.text, marginLeft: 6, marginTop: 12 }]}>Email</Text>
              <Text style={[styles.fieldInput, { borderColor: colors.border, color: colors.text, backgroundColor: colors.background, paddingVertical: 12, paddingHorizontal: 12, borderWidth: 1, borderRadius: 12 }]}>{profileForm.email || 'Not set'}</Text>
              <Text style={[styles.fieldLabel, { color: colors.text, marginLeft: 6, marginTop: 12 }]}>Phone</Text>
              <Text style={[styles.fieldInput, { borderColor: colors.border, color: colors.text, backgroundColor: colors.background, paddingVertical: 12, paddingHorizontal: 12, borderWidth: 1, borderRadius: 12 }]}>{profileForm.phone || 'Not set'}</Text>
            </>
          )}
        </View>

        <View style={[styles.infoSection, { backgroundColor: colors.surface, borderColor: colors.border }]}> 
          <View style={styles.sectionRow}>
            <Text style={[styles.sectionHeading, { color: colors.text }]}>Address Information</Text>
            <TouchableOpacity style={[styles.editButton, { borderColor: colors.border, backgroundColor: colors.background }]} onPress={() => toggleEdit('address')}>
              <Text style={[styles.editButtonText, { color: colors.primary }]}>{editing.address ? 'Save' : 'Edit'}</Text>
            </TouchableOpacity>
          </View>
          {editing.address ? (
            <>
              <Text style={[styles.fieldLabel, { color: colors.text, marginLeft: 6 }]}>Street</Text>
              <TextInput value={addressForm.address_street} onChangeText={(value) => setAddressForm((current) => ({ ...current, address_street: value }))} style={[styles.fieldInput, { borderColor: colors.border, color: colors.text, backgroundColor: colors.background }]} />
              <Text style={[styles.fieldLabel, { color: colors.text, marginLeft: 6, marginTop: 12 }]}>City</Text>
              <TextInput value={addressForm.address_city} onChangeText={(value) => setAddressForm((current) => ({ ...current, address_city: value }))} style={[styles.fieldInput, { borderColor: colors.border, color: colors.text, backgroundColor: colors.background }]} />
              <Text style={[styles.fieldLabel, { color: colors.text, marginLeft: 6, marginTop: 12 }]}>Province</Text>
              <TextInput value={addressForm.address_state} onChangeText={(value) => setAddressForm((current) => ({ ...current, address_state: value }))} style={[styles.fieldInput, { borderColor: colors.border, color: colors.text, backgroundColor: colors.background }]} placeholder="Province" placeholderTextColor={colors.textMuted} />
              <Text style={[styles.fieldLabel, { color: colors.text, marginLeft: 6, marginTop: 12 }]}>Postal Code</Text>
              <TextInput value={addressForm.address_zip} onChangeText={(value) => setAddressForm((current) => ({ ...current, address_zip: value }))} style={[styles.fieldInput, { borderColor: colors.border, color: colors.text, backgroundColor: colors.background }]} placeholder="Postal code" placeholderTextColor={colors.textMuted} />
              <TouchableOpacity style={[styles.submitButton, { backgroundColor: colors.primary }]} onPress={handleUpdateAddress}>
                <Text style={styles.submitText}>Save Address</Text>
              </TouchableOpacity>
            </>
          ) : (
            <>
              <Text style={[styles.fieldLabel, { color: colors.text, marginLeft: 6 }]}>Street</Text>
              <Text style={[styles.fieldInput, { borderColor: colors.border, color: colors.text, backgroundColor: colors.background, paddingVertical: 12, paddingHorizontal: 12, borderWidth: 1, borderRadius: 12 }]}>{addressForm.address_street || 'Not set'}</Text>
              <Text style={[styles.fieldLabel, { color: colors.text, marginLeft: 6, marginTop: 12 }]}>City</Text>
              <Text style={[styles.fieldInput, { borderColor: colors.border, color: colors.text, backgroundColor: colors.background, paddingVertical: 12, paddingHorizontal: 12, borderWidth: 1, borderRadius: 12 }]}>{addressForm.address_city || 'Not set'}</Text>
              <Text style={[styles.fieldLabel, { color: colors.text, marginLeft: 6, marginTop: 12 }]}>Province</Text>
              <Text style={[styles.fieldInput, { borderColor: colors.border, color: colors.text, backgroundColor: colors.background, paddingVertical: 12, paddingHorizontal: 12, borderWidth: 1, borderRadius: 12 }]}>{addressForm.address_state || 'Not set'}</Text>
              <Text style={[styles.fieldLabel, { color: colors.text, marginLeft: 6, marginTop: 12 }]}>Postal Code</Text>
              <Text style={[styles.fieldInput, { borderColor: colors.border, color: colors.text, backgroundColor: colors.background, paddingVertical: 12, paddingHorizontal: 12, borderWidth: 1, borderRadius: 12 }]}>{addressForm.address_zip || 'Not set'}</Text>
            </>
          )}
        </View>

        <View style={[styles.infoSection, { backgroundColor: colors.surface, borderColor: colors.border }]}> 
          <View style={styles.sectionRow}>
            <Text style={[styles.sectionHeading, { color: colors.text }]}>Account Settings</Text>
            <TouchableOpacity style={[styles.editButton, { borderColor: colors.border, backgroundColor: colors.background }]} onPress={() => toggleEdit('password')}>
              <Text style={[styles.editButtonText, { color: colors.primary }]}>{editing.password ? 'Save' : 'Edit'}</Text>
            </TouchableOpacity>
          </View>
          {editing.password ? (
            <>
              <Text style={[styles.fieldLabel, { color: colors.text, marginLeft: 6 }]}>Current Password</Text>
              <TextInput value={passwordForm.current_password} onChangeText={(value) => setPasswordForm((current) => ({ ...current, current_password: value }))} secureTextEntry style={[styles.fieldInput, { borderColor: colors.border, color: colors.text, backgroundColor: colors.background }]} />
              <Text style={[styles.fieldLabel, { color: colors.text, marginLeft: 6, marginTop: 12 }]}>New Password</Text>
              <TextInput value={passwordForm.new_password} onChangeText={(value) => setPasswordForm((current) => ({ ...current, new_password: value }))} secureTextEntry style={[styles.fieldInput, { borderColor: colors.border, color: colors.text, backgroundColor: colors.background }]} />
              <Text style={[styles.fieldLabel, { color: colors.text, marginLeft: 6, marginTop: 12 }]}>Confirm Password</Text>
              <TextInput value={passwordForm.confirm_password} onChangeText={(value) => setPasswordForm((current) => ({ ...current, confirm_password: value }))} secureTextEntry style={[styles.fieldInput, { borderColor: colors.border, color: colors.text, backgroundColor: colors.background }]} />
              <TouchableOpacity style={[styles.submitButton, { backgroundColor: colors.primary }]} onPress={handleChangePassword}>
                <Text style={styles.submitText}>Save Account Information</Text>
              </TouchableOpacity>
            </>
          ) : (
            <Text style={[styles.fieldInput, { borderColor: colors.border, color: colors.textLight, backgroundColor: colors.background, paddingVertical: 12, paddingHorizontal: 12, borderWidth: 1, borderRadius: 12 }]}>Password is hidden for security</Text>
          )}
        </View>

        <TouchableOpacity style={[styles.dangerButton, { backgroundColor: colors.danger }]} onPress={handleLogout}>
          <Text style={styles.logoutButtonText}>Logout</Text>
        </TouchableOpacity>
      </ScrollView>
    </View>
  );
}

function isAvailableFlag(value: boolean | number | string | undefined) {
  if (value === undefined || value === null) return true;
  return value === true || value === 1 || value === '1';
}

function isItemAvailable(item?: { Is_Available?: boolean | number | string; is_available?: boolean | number | string }) {
  if (!item) return false;
  const value = item.Is_Available ?? item.is_available;
  return isAvailableFlag(value);
}

const PRODUCT_CATEGORIES = [
  { title: 'Surface Cleaners', copy: 'Specialized formulas for all surfaces in your home or office', icon: 'water-outline' as const },
  { title: 'Disinfectants', copy: 'Hospital-grade disinfectants to keep your space germ-free', icon: 'shield-checkmark-outline' as const },
  { title: 'Air Fresheners', copy: 'Natural and long-lasting scents for every space', icon: 'leaf-outline' as const },
  { title: 'Cleaning Tools', copy: 'Professional-grade tools used by our cleaning experts', icon: 'brush-outline' as const },
];

function ComingSoonSection() {
  const { colors } = useTheme();
  const [email, setEmail] = useState('');
  const [message, setMessage] = useState('');

  const handleNotify = () => {
    const trimmed = email.trim();
    if (!trimmed || !trimmed.includes('@')) {
      setMessage('Please enter a valid email address.');
      return;
    }
    setMessage("We'll notify you when our product line launches.");
    setEmail('');
  };

  return (
    <View style={[styles.comingSoonCard, { backgroundColor: colors.surface, borderColor: colors.border }]}>
      <View style={[styles.comingSoonIconWrap, { backgroundColor: colors.backgroundTint }]}>
        <Ionicons name="brush" size={42} color={colors.accent} />
      </View>
      <Text style={[styles.comingSoonTitle, { color: colors.primary }]}>Coming Soon!</Text>
      <Text style={[styles.comingSoonCopy, { color: colors.textLight }]}>
        We're currently curating our selection of premium cleaning products for you to purchase. Sign up below to be notified when our product line launches.
      </Text>

      <View style={styles.notifyRow}>
        <TextInput
          value={email}
          onChangeText={setEmail}
          autoCapitalize="none"
          keyboardType="email-address"
          placeholder="Enter your email address"
          placeholderTextColor={colors.textMuted}
          style={[styles.notifyInput, { borderColor: colors.border, color: colors.text, backgroundColor: colors.background }]}
        />
        <TouchableOpacity style={[styles.notifyButton, { backgroundColor: colors.primary }]} onPress={handleNotify}>
          <Text style={styles.notifyButtonText}>Notify Me</Text>
        </TouchableOpacity>
      </View>
           {message ? <Text style={[styles.helperText, { color: colors.primary, textAlign: 'center', marginBottom: 8 }]}>{message}</Text> : null}

      <View style={styles.categoryGrid}>
        {PRODUCT_CATEGORIES.map((category) => (
          <View key={category.title} style={[styles.categoryCard, { backgroundColor: colors.backgroundTint, borderColor: colors.border }]}>
            <View style={[styles.categoryIconWrap, { backgroundColor: colors.surface }]}>
              <Ionicons name={category.icon} size={22} color={colors.primary} />
            </View>
            <Text style={[styles.categoryTitle, { color: colors.primary }]}>{category.title}</Text>
            <Text style={[styles.categoryCopy, { color: colors.textLight }]}>{category.copy}</Text>
          </View>
        ))}
      </View>
    </View>
  );
}

function NoServicesState() {
  const { colors } = useTheme();

  return (
    <View style={[styles.comingSoonCard, { backgroundColor: colors.surface, borderColor: colors.border, paddingVertical: 40 }]}>
      <View style={[styles.comingSoonIconWrap, { backgroundColor: colors.backgroundTint }]}>
        <Ionicons name="search-outline" size={40} color={colors.primary} />

      </View>
      <Text style={[styles.emptyTitle, { color: colors.text }]}>No services</Text>
      <Text style={[styles.emptyCopy, { color: colors.textLight }]}>
        There are no cleaning services available right now. Please check back soon.
      </Text>
    </View>
  );
}

export default function CustomerTabs() {
  const { colors } = useTheme();
  const insets = useSafeAreaInsets();
  const tabBarHeight = 64 + Math.max(insets.bottom, 10);

  const hiddenTabOptions = {
    tabBarButton: () => null,
    tabBarItemStyle: {
      display: 'none' as const,
      width: 0,
      minWidth: 0,
      maxWidth: 0,
      flex: 0,
      padding: 0,
      margin: 0,
    },
  };

  useEffect(() => {
    useCartStore.getState().hydrate();
  }, []);

  return (
    <Tab.Navigator
      screenOptions={{
        headerShown: false,
        tabBarActiveTintColor: colors.primary,
        tabBarInactiveTintColor: colors.textLight,
        tabBarHideOnKeyboard: true,
        tabBarStyle: {
          backgroundColor: colors.surface,
          borderTopColor: colors.border,
          height: tabBarHeight,
          paddingTop: 8,
          paddingBottom: Math.max(insets.bottom, 10),
          paddingHorizontal: 4,
          elevation: 0,
          shadowOpacity: 0,
          borderTopLeftRadius: 24,
          borderTopRightRadius: 24,
          borderTopWidth: 1,
        },
        tabBarItemStyle: {
          flex: 1,
          justifyContent: 'center',
          alignItems: 'center',
          paddingHorizontal: 0,
          marginHorizontal: 0,
        },
        tabBarLabelStyle: {
          fontSize: 11,
          fontWeight: '700',
          marginTop: 2,
        },
      }}
    >
      <Tab.Screen name="Home" component={HomeScreen} options={{ tabBarLabel: 'Home', tabBarIcon: ({ color }: { color: string }) => <Ionicons name="home" size={22} color={color} /> }} />
      <Tab.Screen name="Bookings" component={BookingsScreen} options={{ tabBarLabel: 'Bookings', tabBarIcon: ({ color }: { color: string }) => <Ionicons name="calendar" size={22} color={color} /> }} />
      <Tab.Screen name="Services" component={ServicesScreen} options={{ tabBarLabel: 'Services', tabBarIcon: ({ color }: { color: string }) => <FontAwesome5 name="box" size={20} color={color} solid /> }} />
      <Tab.Screen name="Products" component={ProductsScreen} options={{ tabBarLabel: 'Products', tabBarIcon: ({ color }: { color: string }) => <Ionicons name="bag" size={22} color={color} /> }} />
      <Tab.Screen name="Profile" component={ProfileScreen} options={{ tabBarLabel: 'Profile', tabBarIcon: ({ color }: { color: string }) => <Ionicons name="person" size={22} color={color} /> }} />
      <Tab.Screen name="BookingForm" component={BookingFormScreen} options={hiddenTabOptions} />
      <Tab.Screen name="Cart" component={CartScreen} options={hiddenTabOptions} />
    </Tab.Navigator>
  );
}
