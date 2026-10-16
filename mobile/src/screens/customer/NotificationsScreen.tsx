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
import { query, where, onSnapshot, collection, updateDoc, doc, getDoc } from 'firebase/firestore';
import { db } from '../../firebase';
import { Notification, ServiceRequest } from '../../types';
import { AppHeader, EmptyState, LoadingScreen } from '../../components/ui';
import { lightColors as C } from '../../theme/colors';
import { fontFamily } from '../../theme/typography';
import { radii } from '../../theme/radii';
import { shadows } from '../../theme/shadows';
import { spacing } from '../../theme/spacing';

export default function NotificationsScreen({ navigation }: any) {
  const { userProfile } = useAuth();
  const [notifications, setNotifications] = useState<Notification[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  useEffect(() => {
    if (!userProfile) return;

    const q = query(
      collection(db, 'notifications'),
      where('recipientId', '==', userProfile.uid)
    );

    const unsubscribe = onSnapshot(q, (snapshot) => {
      const notificationsData: Notification[] = [];
      snapshot.forEach((doc) => {
        notificationsData.push({
          id: doc.id,
          ...doc.data(),
        } as Notification);
      });
      notificationsData.sort((a, b) => new Date(b.dateCreated).getTime() - new Date(a.dateCreated).getTime());
      setNotifications(notificationsData);
      setLoading(false);
      setRefreshing(false);
    }, (error) => {
      console.error('Error fetching notifications:', error);
      setLoading(false);
      setRefreshing(false);
    });

    return () => unsubscribe();
  }, [userProfile]);

  const onRefresh = () => {
    setRefreshing(true);
  };

  const markAsRead = async (notificationId: string) => {
    try {
      await updateDoc(doc(db, 'notifications', notificationId), {
        isRead: true,
      });
    } catch (error) {
      console.error('Error marking notification as read:', error);
    }
  };

  const handleNotificationPress = async (notification: Notification) => {
    if (!notification.isRead) {
      await markAsRead(notification.id);
    }
    if (!notification.requestId || !userProfile) return;

    try {
      const snap = await getDoc(doc(db, 'requests', notification.requestId));
      if (!snap.exists()) return;
      const request = { id: snap.id, ...snap.data() } as ServiceRequest;
      if (userProfile.role === 'employee') {
        navigation.navigate('JobDetail', { job: request });
      } else {
        navigation.navigate('RequestDetail', { request });
      }
    } catch (error) {
      console.error('Error opening request from notification:', error);
    }
  };

  const renderNotification = ({ item }: { item: Notification }) => (
    <TouchableOpacity
      style={[styles.notificationCard, !item.isRead && styles.unreadCard]}
      activeOpacity={0.8}
      onPress={() => handleNotificationPress(item)}
    >
      <View style={[styles.iconWrap, !item.isRead && { backgroundColor: C.goldTint }]}>
        <Ionicons
          name={item.isRead ? 'notifications-outline' : 'notifications'}
          size={18}
          color={item.isRead ? C.textMuted : C.accentDark}
        />
      </View>
      <View style={{ flex: 1 }}>
        <Text style={[styles.message, !item.isRead && { color: C.text }]}>
          {item.message}
        </Text>
        <Text style={styles.date}>
          {new Date(item.dateCreated).toLocaleDateString()} at{' '}
          {new Date(item.dateCreated).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
        </Text>
      </View>
      {!item.isRead && <View style={styles.unreadDot} />}
    </TouchableOpacity>
  );

  const unreadCount = notifications.filter((n) => !n.isRead).length;

  if (loading) {
    return <LoadingScreen />;
  }

  return (
    <View style={styles.container}>
      <AppHeader title="Notifications" subtitle="Updates on your requests">
        {unreadCount > 0 && (
          <View style={styles.badge}>
            <Text style={styles.badgeText}>{unreadCount} new</Text>
          </View>
        )}
      </AppHeader>

      {notifications.length === 0 ? (
        <EmptyState
          icon="notifications-off-outline"
          title="No notifications"
          message="Updates about your requests will show up here."
        />
      ) : (
        <FlatList
          data={notifications}
          renderItem={renderNotification}
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
  badge: {
    alignSelf: 'flex-start',
    backgroundColor: C.accent,
    borderRadius: radii.pill,
    paddingHorizontal: spacing.md,
    paddingVertical: 4,
    marginTop: spacing.md,
  },
  badgeText: {
    fontFamily: fontFamily.bodyBold,
    fontSize: 12,
    color: C.primary,
  },
  listContent: {
    padding: spacing.xl,
  },
  notificationCard: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    backgroundColor: C.surface,
    borderRadius: radii.lg,
    borderWidth: 1,
    borderColor: C.border,
    padding: spacing.lg,
    marginBottom: spacing.sm,
    ...shadows.card,
  },
  unreadCard: {
    borderColor: C.accent,
  },
  iconWrap: {
    width: 38,
    height: 38,
    borderRadius: 12,
    backgroundColor: C.neutralTint,
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: spacing.md,
  },
  message: {
    fontFamily: fontFamily.bodyBold,
    fontSize: 14,
    color: C.textLight,
    lineHeight: 20,
  },
  date: {
    fontFamily: fontFamily.body,
    fontSize: 11.5,
    color: C.textMuted,
    marginTop: 5,
  },
  unreadDot: {
    width: 8,
    height: 8,
    borderRadius: 4,
    backgroundColor: C.accent,
    marginLeft: spacing.sm,
    marginTop: 5,
  },
});
