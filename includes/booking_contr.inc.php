<?php

// Booking Controller - Handles business logic for bookings
// This converts the Node.js booking controller to PHP

require_once 'booking_model.inc.php';
require_once 'service_model.inc.php';
require_once 'signup_model.inc.php';
require_once 'notifications_model.inc.php';

/**
 * Send notification for booking status change
 */
function sendBookingNotification($pdo, $bookingId, $newStatus, $changedBy = 'system') {
    try {
        $booking = getBookingById($pdo, $bookingId);
        if (!$booking) return false;

        $service = getServiceById($pdo, $booking['Service_ID']);
        $serviceName = $service ? $service['Name'] : 'Service';
        
        // Determine notification recipient and message based on status
        if ($changedBy === 'admin') {
            // Notify customer
            $recipientId = $booking['Customer_ID'];
            $recipientType = 'customer';
            
            $messages = [
                'contacted' => [
                    'title' => 'We\'re Contacting You!',
                    'message' => "Our team is reviewing your {$serviceName} booking request. We'll be in touch soon."
                ],
                'quoted' => [
                    'title' => 'Quote Received',
                    'message' => "Your quote for {$serviceName} is ready. Please review and confirm."
                ],
                'confirmed' => [
                    'title' => 'Booking Confirmed!',
                    'message' => "Your {$serviceName} booking for " . date('M d, Y', strtotime($booking['Date'])) . " has been confirmed."
                ],
                'in_progress' => [
                    'title' => 'Service In Progress',
                    'message' => "Our team is now working on your {$serviceName} booking."
                ],
                'completed' => [
                    'title' => 'Service Completed',
                    'message' => "Your {$serviceName} service has been completed. Thank you for choosing us!"
                ],
                'cancelled' => [
                    'title' => 'Booking Cancelled',
                    'message' => "Your {$serviceName} booking has been cancelled."
                ],
                'rejected' => [
                    'title' => 'Booking Rejected',
                    'message' => "Your {$serviceName} booking request could not be processed at this time."
                ]
            ];
        } else {
            // Notify admin
            $recipientId = 1; // Admin ID - you may need to adjust this
            $recipientType = 'admin';
            
            $messages = [
                'requested' => [
                    'title' => 'New Booking Request',
                    'message' => "New {$serviceName} booking request from customer ID {$booking['Customer_ID']}."
                ],
                'quoted' => [
                    'title' => 'Quote Accepted',
                    'message' => "Customer has accepted the quote for booking #{$bookingId}."
                ]
            ];
        }

        if (isset($messages[$newStatus])) {
            return create_notification($pdo, $recipientId, $recipientType, $messages[$newStatus]['title'], $messages[$newStatus]['message'], 'booking');
        }
        
        return false;
    } catch (Exception $e) {
        error_log('Notification error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Create a new booking with full validation
 */
function createBookingController($pdo, $data, $customerId) {
    error_log('CREATE BOOKING REQUEST: ' . json_encode($data));
    
    // ==================== VALIDATION CHECKS ====================
    
    // 1. Authentication check
    if (!$customerId) {
        error_log('UNAUTHENTICATED ACCESS ATTEMPT');
        return [
            'success' => false,
            'message' => 'Please log in to request a quotation.',
            'code' => 401
        ];
    }
    
    // 2. Required fields check
    if (empty($data['Service_ID']) || empty($data['Date'])) {
        error_log('MISSING REQUIRED FIELDS: ' . json_encode(['Service_ID' => $data['Service_ID'] ?? null, 'Date' => $data['Date'] ?? null]));
        return [
            'success' => false,
            'message' => 'Service and Date are required fields.',
            'code' => 400
        ];
    }
    
    // 3. Address validation
    $addressFields = ['Address_Street', 'Address_City', 'Address_State', 'Address_Postal_Code'];
    foreach ($addressFields as $field) {
        if (empty($data[$field])) {
            error_log('INCOMPLETE ADDRESS PROVIDED: missing ' . $field);
            return [
                'success' => false,
                'message' => 'Complete address is required including Street, City, State and Postal Code.',
                'code' => 400
            ];
        }
    }
    
    // ==================== DATE VALIDATION ====================
    
    $requestedDate = $data['Date'];
    $requestedDateObj = strtotime($requestedDate);
    
    if (!$requestedDateObj) {
        error_log('INVALID DATE FORMAT: ' . $requestedDate);
        return [
            'success' => false,
            'message' => 'Invalid date format. Please use YYYY-MM-DD format.',
            'code' => 400
        ];
    }
    
    $normalizedRequestedDate = date('Y-m-d', $requestedDateObj);
    $today = date('Y-m-d');
    
    error_log('DATE VALIDATION: requested=' . $normalizedRequestedDate . ', today=' . $today);
    
    // Prevent past dates
    if ($normalizedRequestedDate < $today) {
        error_log('PAST DATE REJECTED: ' . $normalizedRequestedDate);
        return [
            'success' => false,
            'message' => 'Cannot request quotations for past dates. Please select today or a future date.',
            'code' => 400
        ];
    }
    
    // ==================== DATABASE VALIDATION ====================
    
    // 4. Validate customer exists
    $customer = getUserById($pdo, $customerId);
    if (!$customer) {
        error_log('CUSTOMER NOT FOUND: ' . $customerId);
        return [
            'success' => false,
            'message' => 'Customer account not found. Please log in and try again.',
            'code' => 404
        ];
    }
    error_log('CUSTOMER VALIDATED: ' . $customer['Full_Name']);
    
    // 5. Validate service exists
    $service = getServiceById($pdo, $data['Service_ID']);
    if (!$service) {
        error_log('SERVICE NOT FOUND: ' . $data['Service_ID']);
        return [
            'success' => false,
            'message' => 'Service not found. Please refresh the page and try again.',
            'code' => 404
        ];
    }
    
    // Check availability
    $isAvailable = $service['Is_Available'] == 1 || $service['Is_Available'] === null;
    error_log('SERVICE AVAILABILITY CHECK: ' . $service['Name'] . ' - ' . ($isAvailable ? 'Available' : 'Unavailable'));
    
    if (!$isAvailable) {
        error_log('SERVICE UNAVAILABLE: ' . $service['Name']);
        return [
            'success' => false,
            'message' => 'This service is currently unavailable. Please choose another service or check back later.',
            'code' => 400
        ];
    }
    error_log('SERVICE VALIDATED: ' . $service['Name']);
    
    // 6. Check for duplicate bookings
    error_log('CHECKING FOR DUPLICATE BOOKINGS...');
    $existingBooking = checkDuplicateBooking($pdo, $customerId, $data['Service_ID'], $normalizedRequestedDate);
    
    if ($existingBooking) {
        error_log('DUPLICATE BOOKING ATTEMPT');
        return [
            'success' => false,
            'message' => 'You have already requested a quotation for this service on the selected date. Please choose a different date or service.',
            'code' => 409
        ];
    }
    error_log('NO DUPLICATE BOOKINGS FOUND');
    
    // ==================== CREATE BOOKING ====================
    
    error_log('CREATING BOOKING IN DATABASE...');
    
    // Build formatted address
    $formattedAddress = trim($data['Address_Street']) . ', ' . 
                        trim($data['Address_City']) . ', ' . 
                        trim($data['Address_State']) . ', ' . 
                        trim($data['Address_Postal_Code']);
    
    // Prepare booking data
    $bookingData = [
        'Customer_ID' => $customerId,
        'Service_ID' => $data['Service_ID'],
        'Date' => $normalizedRequestedDate,
        'Time' => !empty($data['Time']) ? $data['Time'] : '09:00:00',
        'Address' => $formattedAddress,
        'Special_Instructions' => !empty($data['Special_Instructions']) ? trim($data['Special_Instructions']) : null,
        'Duration' => !empty($data['Duration']) ? (int)$data['Duration'] : $service['Duration'],
        'Status' => 'requested',
        'Property_Type' => !empty($data['Property_Type']) ? trim($data['Property_Type']) : null,
        'Property_Size' => !empty($data['Property_Size']) ? trim($data['Property_Size']) : null,
        'Cleaning_Frequency' => !empty($data['Cleaning_Frequency']) ? trim($data['Cleaning_Frequency']) : null
    ];
    
    error_log('BOOKING DATA: ' . json_encode($bookingData));
    
    try {
        $bookingId = createBooking($pdo, $bookingData);
        $newBooking = getBookingById($pdo, $bookingId);
        
        error_log('BOOKING CREATED SUCCESSFULLY - ID: ' . $bookingId);
        
        // Send notification to admin for new booking request
        sendBookingNotification($pdo, $bookingId, 'requested', 'customer');
        
        return [
            'success' => true,
            'message' => 'Quotation request submitted successfully! We will contact you within 24 hours.',
            'booking' => $newBooking,
            'nextSteps' => [
                'Our team will review your request',
                'You will receive a quotation via email',
                'We may contact you for additional details'
            ]
        ];
        
    } catch (Exception $e) {
        error_log('CREATE BOOKING ERROR: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Unable to process your quotation request at this time. Please try again later.',
            'code' => 500
        ];
    }
}

/**
 * Check booking availability
 */
function checkBookingAvailabilityController($pdo, $customerId, $serviceId, $requestedDate) {
    try {
        error_log('CHECKING BOOKING AVAILABILITY');
        
        // Validate parameters
        if (!$customerId || !$serviceId || !$requestedDate) {
            return [
                'success' => false,
                'message' => 'Customer ID, Service ID, and Date are required.',
                'code' => 400
            ];
        }
        
        // Parse and validate date
        $dateObj = strtotime($requestedDate);
        if (!$dateObj) {
            return [
                'success' => false,
                'message' => 'Invalid date format.',
                'code' => 400
            ];
        }
        
        $normalizedDate = date('Y-m-d', $dateObj);
        $today = date('Y-m-d');
        $currentHour = (int)date('H');
        $currentMinutes = (int)date('i');
        
        $available = true;
        $message = 'Booking available';
        $constraints = [];
        
        // Check 1: Past dates
        if ($normalizedDate < $today) {
            $available = false;
            $message = 'Cannot book for past dates';
            $constraints[] = 'past_date';
        }
        
        // Check 2: Same-day booking rules
        if ($available && $normalizedDate === $today) {
            $currentTimeTotal = $currentHour * 60 + $currentMinutes;
            $cutoffTimeTotal = 12 * 60;
            
            if ($currentTimeTotal >= $cutoffTimeTotal) {
                $available = false;
                $message = 'Same-day bookings must be made before 12:00 PM';
                $constraints[] = 'after_12pm_same_day';
            }
        }
        
        // Check 3: Duplicate bookings
        if ($available) {
            $existingBooking = checkDuplicateBooking($pdo, $customerId, $serviceId, $normalizedDate);
            if ($existingBooking) {
                $available = false;
                $message = 'You already have a booking for this service on the selected date';
                $constraints[] = 'duplicate_booking';
            }
        }
        
        return [
            'success' => true,
            'available' => $available,
            'message' => $message,
            'constraints' => $constraints,
            'date' => $normalizedDate,
            'customerId' => $customerId,
            'serviceId' => $serviceId
        ];
        
    } catch (Exception $e) {
        error_log('CHECK BOOKING AVAILABILITY ERROR: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Unable to check availability at this time. Please try again.',
            'code' => 500
        ];
    }
}

/**
 * Get all bookings for admin
 */
function getAllBookingsController($pdo, $filters = []) {
    try {
        error_log('Fetching all bookings with filters: ' . json_encode($filters));
        
        $bookings = getAllBookings($pdo, $filters);
        
        // Pagination
        $page = $filters['page'] ?? 1;
        $limit = $filters['limit'] ?? 10;
        $total = count($bookings);
        $totalPages = ceil($total / $limit);
        $offset = ($page - 1) * $limit;
        
        $paginatedBookings = array_slice($bookings, $offset, $limit);
        
        return [
            'success' => true,
            'bookings' => $paginatedBookings,
            'totalPages' => $totalPages,
            'currentPage' => $page,
            'totalBookings' => $total
        ];
        
    } catch (Exception $e) {
        error_log('Get bookings error: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error fetching bookings: ' . $e->getMessage()
        ];
    }
}

/**
 * Get booking by ID
 */
function getBookingByIdController($pdo, $id) {
    try {
        $booking = getBookingById($pdo, $id);
        
        if (!$booking) {
            return [
                'success' => false,
                'message' => 'Booking not found.',
                'code' => 404
            ];
        }
        
        return [
            'success' => true,
            'booking' => $booking
        ];
        
    } catch (Exception $e) {
        error_log('Get booking error: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error fetching booking: ' . $e->getMessage()
        ];
    }
}

/**
 * Get customer bookings
 */
function getCustomerBookingsController($pdo, $customerId, $page = 1, $limit = 10) {
    try {
        $bookings = getBookingsByCustomerId($pdo, $customerId, $page, $limit);
        $total = countBookingsByCustomer($pdo, $customerId);
        $totalPages = ceil($total / $limit);
        
        return [
            'success' => true,
            'bookings' => $bookings,
            'totalPages' => $totalPages,
            'currentPage' => $page,
            'totalBookings' => $total
        ];
        
    } catch (Exception $e) {
        error_log('Get customer bookings error: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error fetching booking history'
        ];
    }
}

/**
 * Update booking
 */
function updateBookingController($pdo, $id, $data) {
    try {
        error_log('UPDATE BOOKING REQUEST: ' . json_encode(['id' => $id, 'data' => $data]));
        
        $booking = getBookingById($pdo, $id);
        if (!$booking) {
            return [
                'success' => false,
                'message' => 'Booking not found.',
                'code' => 404
            ];
        }
        
        $updateData = [];
        $allowedFields = [
            'Date', 'Time', 'Special_Instructions', 'Duration',
            'Property_Type', 'Property_Size', 'Cleaning_Frequency'
        ];
        
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $updateData[$field] = $data[$field];
            }
        }
        
        if (empty($updateData)) {
            return [
                'success' => false,
                'message' => 'No valid fields to update.',
                'code' => 400
            ];
        }
        
        updateBooking($pdo, $id, $updateData);
        $updatedBooking = getBookingById($pdo, $id);
        
        return [
            'success' => true,
            'message' => 'Booking updated successfully',
            'booking' => $updatedBooking
        ];
        
    } catch (Exception $e) {
        error_log('Update booking error: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error updating booking: ' . $e->getMessage()
        ];
    }
}

/**
 * Update booking status with workflow management
 */
function updateBookingStatusController($pdo, $id, $status, $additionalData = [], $adminId = null) {
    try {
        error_log('UPDATE BOOKING STATUS: ' . json_encode(['id' => $id, 'status' => $status]));
        
        if (empty($status)) {
            return [
                'success' => false,
                'message' => 'Status is required.',
                'code' => 400
            ];
        }
        
        $booking = getBookingById($pdo, $id);
        if (!$booking) {
            return [
                'success' => false,
                'message' => 'Booking not found.',
                'code' => 404
            ];
        }
        
        $oldStatus = $booking['Status'];
        
        // Prepare update data
        $updateData = [
            'Status' => $status,
            'last_updated' => date('Y-m-d H:i:s')
        ];
        
        // Add timestamps based on status
        switch ($status) {
            case 'contacted':
                $updateData['contact_date'] = $additionalData['contact_date'] ?? date('Y-m-d H:i:s');
                if (!empty($additionalData['call_notes'])) {
                    $updateData['call_notes'] = $additionalData['call_notes'];
                }
                if (!empty($additionalData['next_steps'])) {
                    $updateData['next_steps'] = $additionalData['next_steps'];
                }
                break;
                
            case 'in_progress':
                if (!empty($additionalData['consultation_date'])) {
                    $updateData['consultation_date'] = $additionalData['consultation_date'];
                }
                if (!empty($additionalData['consultation_type'])) {
                    $updateData['consultation_type'] = $additionalData['consultation_type'];
                }
                if (!empty($additionalData['consultation_notes'])) {
                    $updateData['consultation_notes'] = $additionalData['consultation_notes'];
                }
                break;
                
            case 'quoted':
                if (!empty($additionalData['quoted_amount'])) {
                    $updateData['Quoted_Amount'] = (float)$additionalData['quoted_amount'];
                }
                if (!empty($additionalData['quote_breakdown'])) {
                    $updateData['quote_breakdown'] = $additionalData['quote_breakdown'];
                }
                if (!empty($additionalData['quote_validity_days'])) {
                    $updateData['quote_validity_days'] = (int)$additionalData['quote_validity_days'];
                }
                if (!empty($additionalData['quote_notes'])) {
                    $updateData['quote_notes'] = $additionalData['quote_notes'];
                }
                break;
                
            case 'completed':
                $updateData['completed_date'] = date('Y-m-d H:i:s');
                break;
                
            case 'cancelled':
                $updateData['cancelled_date'] = date('Y-m-d H:i:s');
                break;
        }
        
        // Save admin notes if provided
        if (!empty($additionalData['admin_notes'])) {
            $updateData['admin_notes'] = $additionalData['admin_notes'];
        }
        
        error_log('Updating booking with: ' . json_encode($updateData));
        
        updateBooking($pdo, $id, $updateData);
        
        // Log status change
        logStatusChange($pdo, $id, $oldStatus, $status, $adminId, $additionalData['notes'] ?? null);
        
        // Send notification for status change
        sendBookingNotification($pdo, $id, $status, $adminId ? 'admin' : 'customer');
        
        $updatedBooking = getBookingById($pdo, $id);
        
        return [
            'success' => true,
            'message' => 'Booking status updated to ' . $status,
            'booking' => $updatedBooking
        ];
        
    } catch (Exception $e) {
        error_log('UPDATE BOOKING STATUS ERROR: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error updating booking status: ' . $e->getMessage()
        ];
    }
}

/**
 * Quick action - Mark as contacted
 */
function markAsContactedController($pdo, $id, $data = [], $adminId = null) {
    $result = updateBookingStatusController($pdo, $id, 'contacted', [
        'call_notes' => $data['call_notes'] ?? null,
        'next_steps' => $data['next_steps'] ?? null
    ], $adminId);
    
    // Create notification for customer if update was successful
    if ($result['success']) {
        require_once 'notifications_model.inc.php';
        $booking = $result['booking'];
        create_notification(
            $pdo,
            $booking['Customer_ID'],
            'customer',
            'Admin Contacted You',
            'The admin from Phambili Ma-Africa has contacted you regarding your booking #' . $id . '. Please check your email or phone for a message from us.',
            'contact'
        );
    }
    
    return $result;
}

/**
 * Quick action - Move to in progress
 */
function moveToInProgressController($pdo, $id, $data = [], $adminId = null) {
    $result = updateBookingStatusController($pdo, $id, 'in_progress', [
        'consultation_date' => $data['consultation_date'] ?? null,
        'consultation_type' => $data['consultation_type'] ?? null,
        'consultation_notes' => $data['consultation_notes'] ?? null
    ], $adminId);
    
    // Create notification for customer if update was successful
    if ($result['success']) {
        require_once 'notifications_model.inc.php';
        $booking = $result['booking'];
        create_notification(
            $pdo,
            $booking['Customer_ID'],
            'customer',
            'Booking In Progress',
            'Your booking #' . $id . ' is now in progress. Our team is working on your request.',
            'status'
        );
    }
    
    return $result;
}

/**
 * Quick action - Provide quotation
 */
function provideQuotationController($pdo, $id, $data, $adminId = null) {
    $quotedAmount = $data['quoted_amount'] ?? null;
    
    if (!$quotedAmount || $quotedAmount <= 0) {
        return [
            'success' => false,
            'message' => 'Valid quoted amount is required.',
            'code' => 400
        ];
    }
    
    $result = updateBookingStatusController($pdo, $id, 'quoted', [
        'quoted_amount' => $quotedAmount,
        'quote_breakdown' => $data['quote_breakdown'] ?? null,
        'quote_notes' => $data['quote_notes'] ?? null,
        'quote_validity_days' => $data['quote_validity_days'] ?? 7
    ], $adminId);
    
    // Create notification for customer if update was successful
    if ($result['success']) {
        require_once 'notifications_model.inc.php';
        $booking = $result['booking'];
        create_notification(
            $pdo,
            $booking['Customer_ID'],
            'customer',
            'Quote Provided',
            'A quote of R ' . number_format($quotedAmount, 2) . ' has been provided for your booking #' . $id . '. Please review and approve or decline.',
            'quote'
        );
    }
    
    return $result;
}

/**
 * Update booking quote (for providing/editing quote without status change)
 */
function updateBookingQuoteController($pdo, $id, $data, $adminId = null) {
    try {
        error_log('UPDATE BOOKING QUOTE: ' . json_encode(['id' => $id, 'data' => $data]));
        
        $booking = getBookingById($pdo, $id);
        if (!$booking) {
            return [
                'success' => false,
                'message' => 'Booking not found.',
                'code' => 404
            ];
        }
        
        $updateData = ['last_updated' => date('Y-m-d H:i:s')];
        
        if (isset($data['quoted_amount'])) {
            $updateData['Quoted_Amount'] = (float)$data['quoted_amount'];
        }
        if (array_key_exists('quote_breakdown', $data)) {
            $updateData['quote_breakdown'] = $data['quote_breakdown'];
        }
        if (isset($data['quote_validity_days'])) {
            $updateData['quote_validity_days'] = (int)$data['quote_validity_days'];
        }
        if (array_key_exists('quote_notes', $data)) {
            $updateData['quote_notes'] = $data['quote_notes'];
        }
        if (isset($data['status'])) {
            $updateData['Status'] = $data['status'];
        }
        
        if ($data['status'] === 'quoted' || $booking['Status'] === 'quoted') {
            $updateData['quoted_date'] = date('Y-m-d H:i:s');
        }
        
        updateBooking($pdo, $id, $updateData);
        $updatedBooking = getBookingById($pdo, $id);
        
        // Create notification for customer when quote is updated
        if (isset($data['quoted_amount'])) {
            require_once 'notifications_model.inc.php';
            $isNewQuote = empty($booking['Quoted_Amount']);
            $amount = number_format($data['quoted_amount'], 2);
            
            if ($isNewQuote) {
                create_notification(
                    $pdo,
                    $booking['Customer_ID'],
                    'customer',
                    'Quote Provided',
                    'A quote of R ' . $amount . ' has been provided for your booking #' . $id . '. Please review and approve or decline.',
                    'quote'
                );
            } else {
                create_notification(
                    $pdo,
                    $booking['Customer_ID'],
                    'customer',
                    'Quote Updated',
                    'Your quote for booking #' . $id . ' has been updated to R ' . $amount . '. Please review the changes.',
                    'quote'
                );
            }
        }
        
        return [
            'success' => true,
            'message' => 'Booking quote updated successfully',
            'booking' => $updatedBooking
        ];
        
    } catch (Exception $e) {
        error_log('UPDATE BOOKING QUOTE ERROR: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error updating booking quote: ' . $e->getMessage()
        ];
    }
}

/**
 * Delete booking
 */
function deleteBookingController($pdo, $id) {
    try {
        $booking = getBookingById($pdo, $id);
        if (!$booking) {
            return [
                'success' => false,
                'message' => 'Booking not found.',
                'code' => 404
            ];
        }
        
        deleteBooking($pdo, $id);
        
        return [
            'success' => true,
            'message' => 'Quotation request deleted successfully'
        ];
        
    } catch (Exception $e) {
        error_log('Delete booking error: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error deleting booking: ' . $e->getMessage()
        ];
    }
}

/**
 * Get booking statistics
 */
function getBookingStatsController($pdo) {
    try {
        $stats = getBookingStats($pdo);
        
        return [
            'success' => true,
            'stats' => $stats
        ];
        
    } catch (Exception $e) {
        error_log('Get booking stats error: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error fetching booking statistics'
        ];
    }
}

/**
 * Get booking analytics
 */
function getBookingAnalyticsController($pdo, $period = 'monthly') {
    try {
        $analytics = getBookingAnalytics($pdo, $period);
        
        return [
            'success' => true,
            'analytics' => $analytics,
            'period' => $period
        ];
        
    } catch (Exception $e) {
        error_log('Get booking analytics error: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error fetching analytics'
        ];
    }
}

/**
 * Get bookings formatted as cards
 */
function getBookingsAsCardsController($pdo, $filters = []) {
    try {
        $page = $filters['page'] ?? 1;
        $limit = $filters['limit'] ?? 12;
        
        $bookings = getAllBookings($pdo, $filters);
        
        // Format as cards
        $bookingCards = array_map(function($booking) {
            return [
                'id' => (int)$booking['ID'],
                'customer' => [
                    'id' => (int)$booking['Customer_ID'],
                    'name' => $booking['Customer_Name'],
                    'email' => $booking['Customer_Email'],
                    'phone' => $booking['Customer_Phone']
                ],
                'service' => [
                    'id' => (int)$booking['Service_ID'],
                    'name' => $booking['Service_Name'],
                    'description' => $booking['Service_Description'],
                    'duration' => $booking['Service_Duration']
                ],
                'date' => $booking['Date'],
                'time' => $booking['Time'],
                'address' => $booking['Address'],
                'specialInstructions' => $booking['Special_Instructions'],
                'status' => $booking['Status'],
                'quotedAmount' => $booking['Quoted_Amount'],
                'propertyType' => $booking['Property_Type'],
                'propertySize' => $booking['Property_Size'],
                'cleaningFrequency' => $booking['Cleaning_Frequency'],
                'createdAt' => $booking['created_at']
            ];
        }, $bookings);
        
        // Pagination
        $total = count($bookingCards);
        $totalPages = ceil($total / $limit);
        $offset = ($page - 1) * $limit;
        $paginatedCards = array_slice($bookingCards, $offset, $limit);
        
        return [
            'success' => true,
            'bookings' => $paginatedCards,
            'totalPages' => $totalPages,
            'currentPage' => $page,
            'totalBookings' => $total
        ];
        
    } catch (Exception $e) {
        error_log('Get bookings as cards error: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error fetching bookings'
        ];
    }
}

/**
 * Get status action message
 */
function getStatusActionMessage($status) {
    $messages = [
        'requested' => 'quotation request received',
        'contacted' => 'marked as contacted - call completed',
        'in_progress' => 'moved to in progress',
        'quoted' => 'quotation provided',
        'confirmed' => 'confirmed by customer',
        'completed' => 'marked as completed',
        'cancelled' => 'cancelled'
    ];
    return $messages[$status] ?? 'updated';
}

/**
 * Get availability for calendar
 */
function getAvailabilityForCalendar($pdo, $serviceId) {
    try {
        error_log('GET AVAILABILITY FOR CALENDAR - Service ID: ' . $serviceId);

        // Get all bookings for the next 90 days
        $startDate = date('Y-m-d');
        $endDate = date('Y-m-d', strtotime('+90 days'));

        $query = "
            SELECT Date, Time
            FROM bookings
            WHERE Date BETWEEN :start_date AND :end_date
            AND Status NOT IN ('cancelled', 'completed')
        ";

        if ($serviceId) {
            $query .= " AND Service_ID = :service_id";
        }

        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':start_date', $startDate);
        $stmt->bindParam(':end_date', $endDate);

        if ($serviceId) {
            $stmt->bindParam(':service_id', $serviceId);
        }

        $stmt->execute();
        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Build booked slots array
        $bookedSlots = [];
        foreach ($bookings as $booking) {
            $date = $booking['Date'];
            $time = substr($booking['Time'], 0, 5); // Get HH:MM format
            if (!isset($bookedSlots[$date])) {
                $bookedSlots[$date] = [];
            }
            $bookedSlots[$date][] = $time;
        }

        // Generate available dates (all dates in the range)
        $availableDates = [];
        $currentDate = strtotime($startDate);
        $endTimestamp = strtotime($endDate);

        while ($currentDate <= $endTimestamp) {
            $dateStr = date('Y-m-d', $currentDate);
            $bookedCount = isset($bookedSlots[$dateStr]) ? count($bookedSlots[$dateStr]) : 0;

            // Consider a date available if it has fewer than 8 bookings
            if ($bookedCount < 8) {
                $availableDates[] = $dateStr;
            }

            $currentDate = strtotime('+1 day', $currentDate);
        }

        return [
            'success' => true,
            'available_dates' => $availableDates,
            'booked_slots' => $bookedSlots
        ];

    } catch (Exception $e) {
        error_log('GET AVAILABILITY ERROR: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Unable to load availability data. Please try again.'
        ];
    }
}

/**
 * Auto-expire quotes that have passed their validity period
 */
function expireQuotes($pdo) {
    $sql = "UPDATE bookings
            SET Status = 'expired',
                cancelled_date = NOW()
            WHERE Status = 'quoted'
            AND DATE_ADD(consultation_date, INTERVAL quote_validity_days DAY) < NOW()";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute();
}

/**
 * Mark bookings as expired when the booked date has passed
 */
function expirePastBookings($pdo) {
    // Get bookings that will be expired
    $sql = "SELECT ID, Customer_ID, Service_ID, Date, Time
            FROM bookings
            WHERE Status IN ('contacted', 'confirmed', 'in_progress', 'quoted')
            AND CONCAT(Date, ' ', COALESCE(Time, '00:00:00')) < NOW()";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $expiringBookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Update status to expired
    $updateSql = "UPDATE bookings
                  SET Status = 'expired',
                      cancelled_date = NOW()
                  WHERE Status IN ('contacted', 'confirmed', 'in_progress', 'quoted')
                  AND CONCAT(Date, ' ', COALESCE(Time, '00:00:00')) < NOW()";
    $updateStmt = $pdo->prepare($updateSql);
    $result = $updateStmt->execute();
    
    // Send notifications for expired bookings
    if ($result && !empty($expiringBookings)) {
        foreach ($expiringBookings as $booking) {
            $service = getServiceById($pdo, $booking['Service_ID']);
            $serviceName = $service ? $service['Name'] : 'Service';
            
            // Notify customer
            create_notification($pdo, $booking['Customer_ID'], 'customer',
                'Booking Expired',
                "Your {$serviceName} booking for " . date('M d, Y', strtotime($booking['Date'])) . " has expired.",
                'booking'
            );
            
            // Notify admin
            create_notification($pdo, 1, 'admin',
                'Booking Expired',
                "Booking #{$booking['ID']} for {$serviceName} has expired.",
                'booking'
            );
        }
    }
    
    return $result;
}

/**
 * Check for upcoming booking expirations and send notifications
 */
function checkUpcomingExpirations($pdo) {
    $now = new DateTime();
    $notificationsSent = 0;
    
    // Get active bookings
    $sql = "SELECT b.ID, b.Customer_ID, b.Service_ID, b.Date, b.Time, s.Name as Service_Name
            FROM bookings b
            LEFT JOIN services s ON b.Service_ID = s.ID
            WHERE b.Status IN ('contacted', 'confirmed', 'in_progress', 'quoted')
            AND CONCAT(b.Date, ' ', COALESCE(b.Time, '00:00:00')) >= NOW()";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($bookings as $booking) {
        $bookingDateTime = new DateTime($booking['Date'] . ' ' . ($booking['Time'] ?? '00:00:00'));
        $interval = $now->diff($bookingDateTime);
        
        // Check if notification was already sent (we'll use a simple approach with a flag)
        // For now, we'll send notifications based on time intervals
        
        // 2 days before expiration
        if ($interval->days == 2 && $interval->h == 0 && $interval->i == 0) {
            create_notification($pdo, 1, 'admin',
                'Booking Expiring Soon',
                "Booking #{$booking['ID']} for {$booking['Service_Name']} expires in 2 days (" . $bookingDateTime->format('M d, Y') . ").",
                'booking'
            );
            $notificationsSent++;
        }
        
        // 24 hours before expiration
        if ($interval->days == 1 && $interval->h == 0 && $interval->i == 0) {
            create_notification($pdo, 1, 'admin',
                'Booking Expiring Soon',
                "Booking #{$booking['ID']} for {$booking['Service_Name']} expires in 24 hours (" . $bookingDateTime->format('M d, Y H:i') . ").",
                'booking'
            );
            $notificationsSent++;
        }
        
        // 30 minutes before expiration
        if ($interval->days == 0 && $interval->h == 0 && $interval->i <= 30 && $interval->i > 0) {
            create_notification($pdo, 1, 'admin',
                'Booking Expiring Soon',
                "Booking #{$booking['ID']} for {$booking['Service_Name']} expires in {$interval->i} minutes!",
                'booking'
            );
            $notificationsSent++;
        }
    }
    
    return $notificationsSent;
}

/**
 * Get quote modification requests
 */
function getQuoteModificationRequests($pdo, $customerId = null) {
    $sql = "SELECT qr.*, b.ID as booking_id, b.Customer_ID, b.Service_ID, b.Date, b.Time,
                   c.Name as customer_name, s.Name as service_name
            FROM quote_modifications qr
            JOIN bookings b ON qr.Booking_ID = b.ID
            JOIN customers c ON b.Customer_ID = c.ID
            JOIN services s ON b.Service_ID = s.ID
            WHERE qr.Status = 'pending'";
    if ($customerId) {
        $sql .= " AND b.Customer_ID = :customer_id";
    }
    $sql .= " ORDER BY qr.created_at DESC";

    $stmt = $pdo->prepare($sql);
    if ($customerId) {
        $stmt->execute([':customer_id' => $customerId]);
    } else {
        $stmt->execute();
    }
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Create quote modification request
 */
function createQuoteModificationRequest($pdo, $bookingId, $customerId, $reason, $requestedChanges) {
    $sql = "INSERT INTO quote_modifications
            (Booking_ID, Customer_ID, Reason, Requested_Changes, Status, created_at)
            VALUES (:booking_id, :customer_id, :reason, :requested_changes, 'pending', NOW())";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        ':booking_id' => $bookingId,
        ':customer_id' => $customerId,
        ':reason' => $reason,
        ':requested_changes' => $requestedChanges
    ]);
    
    // Notify admin about quote modification request
    if ($result) {
        $booking = getBookingById($pdo, $bookingId);
        $service = getServiceById($pdo, $booking['Service_ID']);
        $serviceName = $service ? $service['Name'] : 'Service';
        
        create_notification($pdo, 1, 'admin', 
            'Quote Modification Request', 
            "Customer has requested a modification for {$serviceName} booking #{$bookingId}.",
            'quote_modification'
        );
    }
    
    return $result;
}
