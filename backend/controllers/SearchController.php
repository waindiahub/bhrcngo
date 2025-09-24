<?php
/**
 * Search Controller
 * Handles search functionality across different content types
 */

require_once __DIR__ . '/../models/Event.php';
require_once __DIR__ . '/../models/Member.php';
require_once __DIR__ . '/../models/Complaint.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/BaseController.php';

class SearchController extends BaseController {
    
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * Global search across all content types
     */
    public function globalSearch() {
        try {
            $query = $_GET['q'] ?? '';
            $type = $_GET['type'] ?? 'all';
            $limit = (int)($_GET['limit'] ?? 10);
            $page = (int)($_GET['page'] ?? 1);
            
            if (empty($query) || strlen($query) < 2) {
                $this->sendError('Search query must be at least 2 characters long');
                return;
            }
            
            $results = [];
            
            switch ($type) {
                case 'events':
                    $results = $this->searchEvents($query, $limit, $page);
                    break;
                case 'members':
                    $results = $this->searchMembers($query, $limit, $page);
                    break;
                case 'complaints':
                    $results = $this->searchComplaints($query, $limit, $page);
                    break;
                case 'users':
                    $results = $this->searchUsers($query, $limit, $page);
                    break;
                case 'all':
                default:
                    $results = $this->searchAll($query, $limit, $page);
                    break;
            }
            
            $this->sendResponse([
                'success' => true,
                'data' => $results,
                'query' => $query,
                'type' => $type
            ]);
            
        } catch (Exception $e) {
            $this->sendError('Search failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Search events
     */
    public function searchEvents($query = null, $limit = 10, $page = 1) {
        try {
            if ($query === null) {
                $query = $_GET['q'] ?? '';
                $limit = (int)($_GET['limit'] ?? 10);
                $page = (int)($_GET['page'] ?? 1);
            }
            
            $eventModel = new Event();
            $events = $eventModel->search($query, $limit, $page);
            
            // Format results
            $formattedEvents = array_map(function($event) {
                return [
                    'id' => $event['id'],
                    'title' => $event['title'],
                    'description' => substr($event['description'], 0, 150) . '...',
                    'date' => $event['event_date'],
                    'location' => $event['location'],
                    'status' => $event['status'],
                    'type' => 'event',
                    'url' => '/events/' . $event['id']
                ];
            }, $events);
            
            if ($query === null) {
                $this->sendResponse([
                    'success' => true,
                    'data' => $formattedEvents,
                    'total' => count($formattedEvents)
                ]);
            } else {
                return $formattedEvents;
            }
            
        } catch (Exception $e) {
            if ($query === null) {
                $this->sendError('Event search failed: ' . $e->getMessage());
            } else {
                return [];
            }
        }
    }
    
    /**
     * Search members
     */
    public function searchMembers($query = null, $limit = 10, $page = 1) {
        try {
            if ($query === null) {
                $query = $_GET['q'] ?? '';
                $limit = (int)($_GET['limit'] ?? 10);
                $page = (int)($_GET['page'] ?? 1);
            }
            
            $memberModel = new Member();
            $members = $memberModel->search($query, $limit, $page);
            
            // Format results
            $formattedMembers = array_map(function($member) {
                return [
                    'id' => $member['id'],
                    'name' => $member['full_name'],
                    'email' => $member['email'],
                    'phone' => $member['phone'],
                    'membership_type' => $member['membership_type'],
                    'status' => $member['status'],
                    'type' => 'member',
                    'url' => '/admin/members/' . $member['id']
                ];
            }, $members);
            
            if ($query === null) {
                $this->sendResponse([
                    'success' => true,
                    'data' => $formattedMembers,
                    'total' => count($formattedMembers)
                ]);
            } else {
                return $formattedMembers;
            }
            
        } catch (Exception $e) {
            if ($query === null) {
                $this->sendError('Member search failed: ' . $e->getMessage());
            } else {
                return [];
            }
        }
    }
    
    /**
     * Search complaints
     */
    public function searchComplaints($query = null, $limit = 10, $page = 1) {
        try {
            if ($query === null) {
                $query = $_GET['q'] ?? '';
                $limit = (int)($_GET['limit'] ?? 10);
                $page = (int)($_GET['page'] ?? 1);
            }
            
            $complaintModel = new Complaint();
            $complaints = $complaintModel->search($query, $limit, $page);
            
            // Format results
            $formattedComplaints = array_map(function($complaint) {
                return [
                    'id' => $complaint['id'],
                    'title' => $complaint['title'],
                    'description' => substr($complaint['description'], 0, 150) . '...',
                    'complainant_name' => $complaint['complainant_name'],
                    'status' => $complaint['status'],
                    'priority' => $complaint['priority'],
                    'created_at' => $complaint['created_at'],
                    'type' => 'complaint',
                    'url' => '/admin/complaints/' . $complaint['id']
                ];
            }, $complaints);
            
            if ($query === null) {
                $this->sendResponse([
                    'success' => true,
                    'data' => $formattedComplaints,
                    'total' => count($formattedComplaints)
                ]);
            } else {
                return $formattedComplaints;
            }
            
        } catch (Exception $e) {
            if ($query === null) {
                $this->sendError('Complaint search failed: ' . $e->getMessage());
            } else {
                return [];
            }
        }
    }
    
    /**
     * Search users
     */
    public function searchUsers($query = null, $limit = 10, $page = 1) {
        try {
            if ($query === null) {
                $query = $_GET['q'] ?? '';
                $limit = (int)($_GET['limit'] ?? 10);
                $page = (int)($_GET['page'] ?? 1);
            }
            
            $userModel = new User();
            $users = $userModel->search($query, $limit, $page);
            
            // Format results
            $formattedUsers = array_map(function($user) {
                return [
                    'id' => $user['id'],
                    'name' => $user['full_name'],
                    'email' => $user['email'],
                    'phone' => $user['phone'],
                    'role' => $user['role'],
                    'status' => $user['status'],
                    'created_at' => $user['created_at'],
                    'type' => 'user',
                    'url' => '/admin/users/' . $user['id']
                ];
            }, $users);
            
            if ($query === null) {
                $this->sendResponse([
                    'success' => true,
                    'data' => $formattedUsers,
                    'total' => count($formattedUsers)
                ]);
            } else {
                return $formattedUsers;
            }
            
        } catch (Exception $e) {
            if ($query === null) {
                $this->sendError('User search failed: ' . $e->getMessage());
            } else {
                return [];
            }
        }
    }
    
    /**
     * Search across all content types
     */
    private function searchAll($query, $limit, $page) {
        $results = [
            'events' => $this->searchEvents($query, 5, 1),
            'members' => $this->searchMembers($query, 5, 1),
            'complaints' => $this->searchComplaints($query, 5, 1),
            'users' => $this->searchUsers($query, 5, 1)
        ];
        
        // Combine all results
        $allResults = [];
        foreach ($results as $type => $items) {
            $allResults = array_merge($allResults, $items);
        }
        
        // Sort by relevance (this is a simple implementation)
        usort($allResults, function($a, $b) use ($query) {
            $scoreA = $this->calculateRelevanceScore($a, $query);
            $scoreB = $this->calculateRelevanceScore($b, $query);
            return $scoreB - $scoreA;
        });
        
        // Apply pagination
        $offset = ($page - 1) * $limit;
        $paginatedResults = array_slice($allResults, $offset, $limit);
        
        return [
            'results' => $paginatedResults,
            'total' => count($allResults),
            'by_type' => [
                'events' => count($results['events']),
                'members' => count($results['members']),
                'complaints' => count($results['complaints']),
                'users' => count($results['users'])
            ]
        ];
    }
    
    /**
     * Calculate relevance score for search results
     */
    private function calculateRelevanceScore($item, $query) {
        $score = 0;
        $queryLower = strtolower($query);
        
        // Check title/name match
        $title = strtolower($item['title'] ?? $item['name'] ?? '');
        if (strpos($title, $queryLower) !== false) {
            $score += 10;
            if (strpos($title, $queryLower) === 0) {
                $score += 5; // Bonus for starting with query
            }
        }
        
        // Check description match
        $description = strtolower($item['description'] ?? '');
        if (strpos($description, $queryLower) !== false) {
            $score += 5;
        }
        
        // Check email match
        $email = strtolower($item['email'] ?? '');
        if (strpos($email, $queryLower) !== false) {
            $score += 8;
        }
        
        // Type-specific scoring
        switch ($item['type']) {
            case 'event':
                if (isset($item['status']) && $item['status'] === 'upcoming') {
                    $score += 3; // Boost upcoming events
                }
                break;
            case 'member':
                if (isset($item['status']) && $item['status'] === 'approved') {
                    $score += 2; // Boost approved members
                }
                break;
            case 'complaint':
                if (isset($item['status']) && $item['status'] === 'pending') {
                    $score += 4; // Boost pending complaints
                }
                break;
        }
        
        return $score;
    }
    
    /**
     * Get search suggestions
     */
    public function getSuggestions() {
        try {
            $query = $_GET['q'] ?? '';
            $limit = (int)($_GET['limit'] ?? 5);
            
            if (empty($query) || strlen($query) < 2) {
                $this->sendResponse([
                    'success' => true,
                    'data' => []
                ]);
                return;
            }
            
            $suggestions = $this->generateSuggestions($query, $limit);
            
            $this->sendResponse([
                'success' => true,
                'data' => $suggestions
            ]);
            
        } catch (Exception $e) {
            $this->sendError('Failed to get suggestions: ' . $e->getMessage());
        }
    }
    
    /**
     * Generate search suggestions
     */
    private function generateSuggestions($query, $limit) {
        $suggestions = [];
        
        // Get suggestions from different sources
        $eventSuggestions = $this->getEventSuggestions($query, $limit);
        $memberSuggestions = $this->getMemberSuggestions($query, $limit);
        
        // Combine and deduplicate
        $allSuggestions = array_merge($eventSuggestions, $memberSuggestions);
        $uniqueSuggestions = array_unique($allSuggestions);
        
        // Limit results
        return array_slice($uniqueSuggestions, 0, $limit);
    }
    
    /**
     * Get event-based suggestions
     */
    private function getEventSuggestions($query, $limit) {
        // This would query the database for event titles/keywords
        // For now, return sample suggestions
        return [
            'Annual Conference',
            'Community Meeting',
            'Training Workshop'
        ];
    }
    
    /**
     * Get member-based suggestions
     */
    private function getMemberSuggestions($query, $limit) {
        // This would query the database for member names/keywords
        // For now, return sample suggestions
        return [
            'John Doe',
            'Jane Smith',
            'Admin User'
        ];
    }
    
    /**
     * Advanced search with filters
     */
    public function advancedSearch() {
        try {
            $filters = [
                'query' => $_GET['q'] ?? '',
                'type' => $_GET['type'] ?? 'all',
                'date_from' => $_GET['date_from'] ?? null,
                'date_to' => $_GET['date_to'] ?? null,
                'status' => $_GET['status'] ?? null,
                'category' => $_GET['category'] ?? null,
                'location' => $_GET['location'] ?? null,
                'limit' => (int)($_GET['limit'] ?? 20),
                'page' => (int)($_GET['page'] ?? 1)
            ];
            
            $results = $this->performAdvancedSearch($filters);
            
            $this->sendResponse([
                'success' => true,
                'data' => $results,
                'filters' => $filters
            ]);
            
        } catch (Exception $e) {
            $this->sendError('Advanced search failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Perform advanced search with filters
     */
    private function performAdvancedSearch($filters) {
        // This would implement complex search logic with filters
        // For now, return basic search results
        return $this->searchAll($filters['query'], $filters['limit'], $filters['page']);
    }
    
    /**
     * Send error response
     */
    private function sendError($message, $code = 400) {
        $this->jsonError($message, $code);
    }
    
    /**
     * Send success response
     */
    private function sendResponse($data, $message = 'Success', $code = 200) {
        $this->jsonResponse([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $code);
    }
}
?>