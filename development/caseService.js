import axios from 'axios';
import { format } from 'date-fns';
import authService from './authService';

// Base API URL
const API_URL = '/wp-json/metcalf/v1';

// Create axios instance with auth token
const api = axios.create({
  baseURL: API_URL,
  headers: {
    'Content-Type': 'application/json'
  }
});

// Add request interceptor to include auth token
api.interceptors.request.use(
  async (config) => {
    const token = await authService.getToken();
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => Promise.reject(error)
);

// Add response interceptor to handle token refresh
api.interceptors.response.use(
  (response) => response,
  async (error) => {
    const originalRequest = error.config;
    
    // If error is 401 and not already retrying
    if (error.response?.status === 401 && !originalRequest._retry) {
      originalRequest._retry = true;
      
      try {
        // Try to refresh token
        await authService.refreshToken();
        
        // Retry the original request with new token
        const token = await authService.getToken();
        originalRequest.headers.Authorization = `Bearer ${token}`;
        return api(originalRequest);
      } catch (refreshError) {
        // If refresh fails, logout user
        authService.logout();
        return Promise.reject(refreshError);
      }
    }
    
    return Promise.reject(error);
  }
);

// Format date for API
const formatDate = (date) => {
  if (!date) return null;
  return format(new Date(date), 'yyyy-MM-dd');
};

// Case Service
const caseService = {
  // Get all cases with optional filters
  getCases: async (filters = {}) => {
    const params = { ...filters };
    const response = await api.get('/cases', { params });
    return response.data;
  },
  
  // Get a single case by ID
  getCase: async (caseId) => {
    const response = await api.get(`/cases/${caseId}`);
    return response.data;
  },
  
  // Create a new case
  createCase: async (caseData) => {
    const response = await api.post('/cases', caseData);
    return response.data;
  },
  
  // Update an existing case
  updateCase: async (caseId, caseData) => {
    const response = await api.put(`/cases/${caseId}`, caseData);
    return response.data;
  },
  
  // Delete a case
  deleteCase: async (caseId) => {
    const response = await api.delete(`/cases/${caseId}`);
    return response.data;
  },
  
  // Get case timeline
  getCaseTimeline: async (caseId) => {
    const response = await api.get(`/cases/${caseId}/timeline`);
    return response.data;
  },
  
  // Add timeline event
  addTimelineEvent: async (caseId, eventData) => {
    const data = {
      ...eventData,
      event_date: formatDate(eventData.event_date)
    };
    const response = await api.post(`/cases/${caseId}/timeline`, data);
    return response.data;
  },
  
  // Update timeline event
  updateTimelineEvent: async (caseId, eventId, eventData) => {
    const data = { ...eventData };
    if (eventData.event_date) {
      data.event_date = formatDate(eventData.event_date);
    }
    const response = await api.put(`/cases/${caseId}/timeline/${eventId}`, data);
    return response.data;
  },
  
  // Delete timeline event
  deleteTimelineEvent: async (caseId, eventId) => {
    const response = await api.delete(`/cases/${caseId}/timeline/${eventId}`);
    return response.data;
  },
  
  // Get case team
  getCaseTeam: async (caseId) => {
    const response = await api.get(`/cases/${caseId}/team`);
    return response.data;
  },
  
  // Add team member
  addTeamMember: async (caseId, userData) => {
    const response = await api.post(`/cases/${caseId}/team`, userData);
    return response.data;
  },
  
  // Update team member
  updateTeamMember: async (caseId, userId, userData) => {
    const response = await api.put(`/cases/${caseId}/team/${userId}`, userData);
    return response.data;
  },
  
  // Remove team member
  removeTeamMember: async (caseId, userId) => {
    const response = await api.delete(`/cases/${caseId}/team/${userId}`);
    return response.data;
  },
  
  // Get case notes
  getCaseNotes: async (caseId) => {
    const response = await api.get(`/cases/${caseId}/notes`);
    return response.data;
  },
  
  // Add case note
  addCaseNote: async (caseId, noteData) => {
    const response = await api.post(`/cases/${caseId}/notes`, noteData);
    return response.data;
  },
  
  // Update case note
  updateCaseNote: async (caseId, noteId, noteData) => {
    const response = await api.put(`/cases/${caseId}/notes/${noteId}`, noteData);
    return response.data;
  },
  
  // Delete case note
  deleteCaseNote: async (caseId, noteId) => {
    const response = await api.delete(`/cases/${caseId}/notes/${noteId}`);
    return response.data;
  },
  
  // Get case deadlines
  getCaseDeadlines: async (caseId, status = 'all') => {
    const response = await api.get(`/cases/${caseId}/deadlines`, { params: { status } });
    return response.data;
  },
  
  // Add case deadline
  addCaseDeadline: async (caseId, deadlineData) => {
    const data = {
      ...deadlineData,
      deadline_date: formatDate(deadlineData.deadline_date)
    };
    
    // Format reminder dates if present
    if (data.reminders && Array.isArray(data.reminders)) {
      data.reminders = data.reminders.map(reminder => ({
        ...reminder,
        time: formatDate(reminder.time)
      }));
    }
    
    const response = await api.post(`/cases/${caseId}/deadlines`, data);
    return response.data;
  },
  
  // Update case deadline
  updateCaseDeadline: async (caseId, deadlineId, deadlineData) => {
    const data = { ...deadlineData };
    if (deadlineData.deadline_date) {
      data.deadline_date = formatDate(deadlineData.deadline_date);
    }
    
    const response = await api.put(`/cases/${caseId}/deadlines/${deadlineId}`, data);
    return response.data;
  },
  
  // Delete case deadline
  deleteCaseDeadline: async (caseId, deadlineId) => {
    const response = await api.delete(`/cases/${caseId}/deadlines/${deadlineId}`);
    return response.data;
  },
  
  // Complete deadline
  completeDeadline: async (caseId, deadlineId) => {
    const response = await api.post(`/cases/${caseId}/deadlines/${deadlineId}/complete`);
    return response.data;
  },
  
  // Get upcoming deadlines
  getUpcomingDeadlines: async (params = {}) => {
    const response = await api.get('/deadlines/upcoming', { params });
    return response.data;
  },
  
  // Update case status
  updateCaseStatus: async (caseId, statusData) => {
    const response = await api.put(`/cases/${caseId}/status`, statusData);
    return response.data;
  },
  
  // Get case status history
  getCaseStatusHistory: async (caseId) => {
    const response = await api.get(`/cases/${caseId}/status/history`);
    return response.data;
  },
  
  // Get case categories
  getCategories: async () => {
    const response = await api.get('/cases/categories');
    return response.data;
  },
  
  // Get case types
  getTypes: async (categoryId = null) => {
    const params = categoryId ? { category_id: categoryId } : {};
    const response = await api.get('/cases/types', { params });
    return response.data;
  },
  
  // Get case statuses
  getStatuses: async () => {
    const response = await api.get('/cases/statuses');
    return response.data;
  },
  
  // Get workflows
  getWorkflows: async (includeInactive = false) => {
    const params = { include_inactive: includeInactive };
    const response = await api.get('/workflows', { params });
    return response.data;
  },
  
  // Get workflow by ID
  getWorkflow: async (workflowId) => {
    const response = await api.get(`/workflows/${workflowId}`);
    return response.data;
  }
};

export default caseService;