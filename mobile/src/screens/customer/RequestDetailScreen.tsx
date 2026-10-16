import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  ScrollView,
  StyleSheet,
  TouchableOpacity,
  Alert,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useAuth } from '../../context/AuthContext';
import { doc, getDoc, setDoc, updateDoc, onSnapshot } from 'firebase/firestore';
import { db } from '../../firebase';
import { ServiceRequest, FeedbackFormData } from '../../types';
import { validateRating, validateCommentLength } from '../../utils/validators';
import { getFirebaseErrorMessage } from '../../utils/errorHandler';
import { AppButton, AppInput, Card, InfoRow, SectionTitle, StatusBadge, statusMeta } from '../../components/ui';
import { lightColors as C } from '../../theme/colors';
import { fontFamily } from '../../theme/typography';
import { radii } from '../../theme/radii';
import { spacing } from '../../theme/spacing';

const TIMELINE_STEPS = ['Pending', 'Approved', 'In Progress', 'Completed'] as const;

function StatusTimeline({ status }: { status: string }) {
  const currentIndex = TIMELINE_STEPS.indexOf(status as (typeof TIMELINE_STEPS)[number]);
  return (
    <View>
      {TIMELINE_STEPS.map((step, index) => {
        const done = index <= currentIndex;
        const isCurrent = index === currentIndex;
        const meta = statusMeta(step);
        return (
          <View key={step} style={styles.stepRow}>
            <View style={styles.stepMarkerCol}>
              <View
                style={[
                  styles.stepDot,
                  { backgroundColor: done ? meta.tint : C.surfaceSoft, borderColor: done ? meta.color : C.border },
                ]}
              >
                <Ionicons
                  name={done ? meta.icon : 'ellipse-outline'}
                  size={14}
                  color={done ? meta.color : C.textMuted}
                />
              </View>
              {index < TIMELINE_STEPS.length - 1 && (
                <View style={[styles.stepLine, done && { backgroundColor: meta.color }]} />
              )}
            </View>
            <Text
              style={[
                styles.stepLabel,
                isCurrent && { color: C.text, fontFamily: fontFamily.bodyBold },
                !done && { color: C.textMuted },
              ]}
            >
              {step}
            </Text>
          </View>
        );
      })}
    </View>
  );
}

interface Props {
  route: any;
  navigation: any;
}

export default function RequestDetailScreen({ route, navigation }: Props) {
  const insets = useSafeAreaInsets();
  const { userProfile } = useAuth();
  const [request, setRequest] = useState<ServiceRequest>(route.params?.request);
  const [feedback, setFeedback] = useState<FeedbackFormData>({
    rating: 5,
    comment: '',
  });
  const [existingFeedback, setExistingFeedback] = useState<any>(null);
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    const unsubscribe = onSnapshot(doc(db, 'requests', route.params?.request?.id), (snap) => {
      if (snap.exists()) setRequest({ id: snap.id, ...snap.data() } as ServiceRequest);
    });
    return unsubscribe;
  }, [route.params?.request?.id]);

  useEffect(() => {
    const loadFeedback = async () => {
      try {
        const feedbackDoc = await getDoc(doc(db, 'feedback', request.id));
        if (feedbackDoc.exists()) {
          setExistingFeedback(feedbackDoc.data());
        }
      } catch (error) {
        console.error('Error loading feedback:', error);
      }
    };
    loadFeedback();
  }, [request.id]);

  const validateFeedbackForm = () => {
    const newErrors: Record<string, string> = {};

    const ratingError = validateRating(feedback.rating);
    if (ratingError) newErrors.rating = ratingError;

    const commentError = validateCommentLength(feedback.comment ?? '');
    if (commentError) newErrors.comment = commentError;

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmitFeedback = async () => {
    if (!validateFeedbackForm()) return;
    if (!userProfile) return;

    setLoading(true);
    try {
      await setDoc(doc(db, 'feedback', request.id), {
        customerId: userProfile.uid,
        rating: feedback.rating,
        comment: feedback.comment,
        dateSubmitted: new Date().toISOString(),
      });

      await updateDoc(doc(db, 'requests', request.id), {
        hasFeedback: true,
      });

      Alert.alert('Success', 'Thank you for your feedback!');
      setExistingFeedback({ rating: feedback.rating, comment: feedback.comment });
    } catch (error) {
      Alert.alert('Error', getFirebaseErrorMessage(error));
    } finally {
      setLoading(false);
    }
  };

  const renderStars = (rating: number, interactive: boolean = false) => (
    <View style={styles.starsContainer}>
      {[1, 2, 3, 4, 5].map((star) => (
        <TouchableOpacity
          key={star}
          disabled={!interactive}
          onPress={() => interactive && setFeedback({ ...feedback, rating: star })}
          hitSlop={{ top: 6, bottom: 6, left: 2, right: 2 }}
        >
          <Ionicons
            name={star <= rating ? 'star' : 'star-outline'}
            size={30}
            color={star <= rating ? C.accent : C.border}
            style={{ marginRight: 4 }}
          />
        </TouchableOpacity>
      ))}
    </View>
  );

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
            <Text style={styles.headerLabel}>Request reference</Text>
            <Text style={styles.refNumber}>{request.refNumber}</Text>
          </View>
          <StatusBadge status={request.status} />
        </View>
      </View>

      <View style={styles.content}>
        {request.status !== 'Rejected' ? (
          <Card style={styles.section}>
            <SectionTitle>Progress</SectionTitle>
            <StatusTimeline status={request.status} />
          </Card>
        ) : (
          <Card style={styles.section}>
            <View style={styles.rejectedRow}>
              <Ionicons name="close-circle" size={22} color={C.danger} />
              <View style={{ flex: 1 }}>
                <Text style={styles.rejectedTitle}>This request was rejected</Text>
                <Text style={styles.rejectedText}>
                  You can book the service again from the Services tab.
                </Text>
              </View>
            </View>
          </Card>
        )}

        <Card style={styles.section}>
          <SectionTitle>Service details</SectionTitle>
          <InfoRow icon="construct-outline" label="Service" value={request.serviceName} />
          {request.employeeName && (
            <InfoRow icon="person-outline" label="Assigned to" value={request.employeeName} />
          )}
        </Card>

        <Card style={styles.section}>
          <SectionTitle>Schedule & location</SectionTitle>
          <InfoRow icon="calendar-outline" label="Date" value={request.requestedDate} />
          <InfoRow icon="time-outline" label="Time" value={request.requestedTime} />
          <InfoRow icon="location-outline" label="Address" value={request.siteAddress} />
        </Card>

        {request.status === 'Completed' && !request.hasFeedback && (
          <Card style={styles.section}>
            <SectionTitle>Rate this service</SectionTitle>
            <Text style={styles.feedbackHint}>How was your experience?</Text>
            {renderStars(feedback.rating, true)}

            <AppInput
              label="Comment (optional)"
              placeholder="Share your experience..."
              value={feedback.comment}
              onChangeText={(text) => {
                setFeedback({ ...feedback, comment: text });
                if (errors.comment) setErrors({ ...errors, comment: '' });
              }}
              multiline
              numberOfLines={4}
              error={errors.comment}
              style={{ textAlignVertical: 'top' }}
            />

            <AppButton
              title="Submit Feedback"
              icon="star-outline"
              loading={loading}
              onPress={handleSubmitFeedback}
            />
          </Card>
        )}

        {existingFeedback && (
          <Card style={styles.section}>
            <SectionTitle>Your feedback</SectionTitle>
            {renderStars(existingFeedback.rating)}
            {!!existingFeedback.comment && (
              <Text style={styles.feedbackComment}>“{existingFeedback.comment}”</Text>
            )}
          </Card>
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
  feedbackHint: {
    fontFamily: fontFamily.body,
    fontSize: 13,
    color: C.textLight,
    marginBottom: spacing.sm,
  },
  starsContainer: {
    flexDirection: 'row',
    marginBottom: spacing.md,
  },
  stepRow: {
    flexDirection: 'row',
    alignItems: 'flex-start',
  },
  stepMarkerCol: {
    alignItems: 'center',
    width: 28,
    marginRight: spacing.md,
  },
  stepDot: {
    width: 28,
    height: 28,
    borderRadius: 14,
    borderWidth: 1.5,
    alignItems: 'center',
    justifyContent: 'center',
  },
  stepLine: {
    width: 2,
    flex: 1,
    minHeight: 18,
    backgroundColor: C.border,
    marginVertical: 2,
  },
  stepLabel: {
    fontFamily: fontFamily.body,
    fontSize: 14,
    color: C.textLight,
    paddingTop: 4,
    paddingBottom: spacing.md,
  },
  rejectedRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  rejectedTitle: {
    fontFamily: fontFamily.bodyBold,
    fontSize: 14,
    color: C.danger,
  },
  rejectedText: {
    fontFamily: fontFamily.body,
    fontSize: 12.5,
    color: C.textLight,
    marginTop: 2,
  },
  feedbackComment: {
    fontFamily: fontFamily.body,
    fontSize: 13.5,
    color: C.textLight,
    fontStyle: 'italic',
    marginTop: 4,
  },
});
