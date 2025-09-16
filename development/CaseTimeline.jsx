import React, { useState, useEffect } from 'react';
import PropTypes from 'prop-types';
import {
  Box,
  Typography,
  Button,
  Paper,
  Divider,
  IconButton,
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions,
  TextField,
  FormControlLabel,
  Checkbox,
  Grid,
  CircularProgress,
  Alert,
  Tooltip
} from '@mui/material';
import { 
  Add as AddIcon,
  Edit as EditIcon,
  Delete as DeleteIcon,
  Check as CheckIcon,
  Close as CloseIcon,
  Event as EventIcon,
  Timeline as TimelineIcon
} from '@mui/icons-material';
import { AdapterDateFns } from '@mui/x-date-pickers/AdapterDateFns';
import { LocalizationProvider, DatePicker } from '@mui/x-date-pickers';
import { format, parseISO } from 'date-fns';

// Import services
import { useAuth } from '../contexts/AuthContext';
import { useNotification } from '../contexts/NotificationContext';
import caseService from '../services/caseService';

// Timeline Event component
const TimelineEvent = ({ event, onEdit, onDelete, onComplete, isEditable }) => {
  const eventDate = parseISO(event.event_date);
  const isCompleted = Boolean(event.is_completed);
  
  return (
    <Paper 
      elevation={1} 
      sx={{ 
        p: 2, 
        mb: 2, 
        borderLeft: 4, 
        borderColor: isCompleted ? 'success.main' : 'primary.main',
        backgroundColor: isCompleted ? 'success.light' : 'white',
        opacity: isCompleted ? 0.9 : 1
      }}
    >
      <Grid container spacing={2} alignItems="center">
        <Grid item xs={12} sm={8}>
          <Typography variant="h6" component="h3">
            {event.event_title}
          </Typography>
          <Typography variant="body2" color="text.secondary" gutterBottom>
            <EventIcon fontSize="small" sx={{ mr: 0.5, verticalAlign: 'middle' }} />
            {format(eventDate, 'MMMM d, yyyy')}
          </Typography>
          {event.event_description && (
            <Typography variant="body1" sx={{ mt: 1 }}>
              {event.event_description}
            </Typography>
          )}
        </Grid>
        
        <Grid item xs={12} sm={4} sx={{ textAlign: 'right' }}>
          {isEditable && (
            <>
              {!isCompleted && (
                <Tooltip title="Mark as Complete">
                  <IconButton 
                    color="success" 
                    onClick={() => onComplete(event.timeline_event_id)}
                    size="small"
                    sx={{ mr: 1 }}
                  >
                    <CheckIcon />
                  </IconButton>
                </Tooltip>
              )}
              <Tooltip title="Edit Event">
                <IconButton 
                  color="primary" 
                  onClick={() => onEdit(event)}
                  size="small"
                  sx={{ mr: 1 }}
                >
                  <EditIcon />
                </IconButton>
              </Tooltip>
              <Tooltip title="Delete Event">
                <IconButton 
                  color="error" 
                  onClick={() => onDelete(event.timeline_event_id)}
                  size="small"
                >
                  <DeleteIcon />
                </IconButton>
              </Tooltip>
            </>
          )}
        </Grid>
      </Grid>
    </Paper>
  );
};

// Timeline Event Form Dialog
const TimelineEventForm = ({ open, event, onClose, onSubmit }) => {
  const [formData, setFormData] = useState({
    event_title: '',
    event_description: '',
    event_date: new Date(),
    is_completed: false
  });
  const [errors, setErrors] = useState({});
  
  // Initialize form with event data if editing
  useEffect(() => {
    if (event) {
      setFormData({
        event_title: event.event_title || '',
        event_description: event.event_description || '',
        event_date: event.event_date ? parseISO(event.event_date) : new Date(),
        is_completed: Boolean(event.is_completed)
      });
    } else {
      setFormData({
        event_title: '',
        event_description: '',
        event_date: new Date(),
        is_completed: false
      });
    }
    setErrors({});
  }, [event, open]);
  
  // Handle form input changes
  const handleChange = (e) => {
    const { name, value, checked } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: name === 'is_completed' ? checked : value
    }));
    
    // Clear error for this field
    if (errors[name]) {
      setErrors(prev => ({ ...prev, [name]: null }));
    }
  };
  
  // Handle date change
  const handleDateChange = (date) => {
    setFormData(prev => ({ ...prev, event_date: date }));
    
    // Clear error for date field
    if (errors.event_date) {
      setErrors(prev => ({ ...prev, event_date: null }));
    }
  };
  
  // Validate form
  const validateForm = () => {
    const newErrors = {};
    
    if (!formData.event_title.trim()) {
      newErrors.event_title = 'Title is required';
    }
    
    if (!formData.event_date) {
      newErrors.event_date = 'Date is required';
    }
    
    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };
  
  // Handle form submission
  const handleSubmit = () => {
    if (validateForm()) {
      onSubmit(formData);
    }
  };
  
  return (
    <Dialog open={open} onClose={onClose} maxWidth="sm" fullWidth>
      <DialogTitle>
        {event ? 'Edit Timeline Event' : 'Add Timeline Event'}
      </DialogTitle>
      <DialogContent>
        <Box sx={{ pt: 1 }}>
          <TextField
            name="event_title"
            label="Event Title"
            value={formData.event_title}
            onChange={handleChange}
            fullWidth
            margin="normal"
            error={Boolean(errors.event_title)}
            helperText={errors.event_title}
            required
          />
          
          <LocalizationProvider dateAdapter={AdapterDateFns}>
            <DatePicker
              label="Event Date"
              value={formData.event_date}
              onChange={handleDateChange}
              renderInput={(params) => (
                <TextField
                  {...params}
                  fullWidth
                  margin="normal"
                  error={Boolean(errors.event_date)}
                  helperText={errors.event_date}
                  required
                />
              )}
            />
          </LocalizationProvider>
          
          <TextField
            name="event_description"
            label="Description"
            value={formData.event_description}
            onChange={handleChange}
            fullWidth
            margin="normal"
            multiline
            rows={4}
          />
          
          <FormControlLabel
            control={
              <Checkbox
                name="is_completed"
                checked={formData.is_completed}
                onChange={handleChange}
                color="primary"
              />
            }
            label="Mark as completed"
            sx={{ mt: 1 }}
          />
        </Box>
      </DialogContent>
      <DialogActions>
        <Button onClick={onClose}>Cancel</Button>
        <Button onClick={handleSubmit} variant="contained" color="primary">
          {event ? 'Update' : 'Add'}
        </Button>
      </DialogActions>
    </Dialog>
  );
};

// Main CaseTimeline component
const CaseTimeline = ({ caseId, timeline = [], onUpdate }) => {
  const { user } = useAuth();
  const { showNotification } = useNotification();
  
  // State
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [timelineEvents, setTimelineEvents] = useState([]);
  const [isFormOpen, setIsFormOpen] = useState(false);
  const [currentEvent, setCurrentEvent] = useState(null);
  
  // Check if user can edit timeline
  const isEditable = user?.roles?.includes('law_firm_staff') || user?.roles?.includes('law_firm_admin');
  
  // Initialize timeline events from props
  useEffect(() => {
    if (timeline && timeline.length > 0) {
      setTimelineEvents(timeline);
    } else {
      fetchTimeline();
    }
  }, [timeline, caseId]);
  
  // Fetch timeline events
  const fetchTimeline = async () => {
    if (!caseId) return;
    
    setLoading(true);
    try {
      const response = await caseService.getCaseTimeline(caseId);
      setTimelineEvents(response.data);
      setError(null);
    } catch (err) {
      setError('Failed to fetch timeline events. Please try again.');
      showNotification('error', 'Failed to fetch timeline events');
      console.error('Error fetching timeline:', err);
    } finally {
      setLoading(false);
    }
  };
  
  // Handle add event
  const handleAddEvent = () => {
    setCurrentEvent(null);
    setIsFormOpen(true);
  };
  
  // Handle edit event
  const handleEditEvent = (event) => {
    setCurrentEvent(event);
    setIsFormOpen(true);
  };
  
  // Handle delete event
  const handleDeleteEvent = async (eventId) => {
    if (window.confirm('Are you sure you want to delete this event? This action cannot be undone.')) {
      try {
        await caseService.deleteTimelineEvent(caseId, eventId);
        showNotification('success', 'Timeline event deleted successfully');
        if (onUpdate) {
          onUpdate(caseId);
        } else {
          fetchTimeline();
        }
      } catch (err) {
        showNotification('error', 'Failed to delete timeline event');
        console.error('Error deleting timeline event:', err);
      }
    }
  };
  
  // Handle complete event
  const handleCompleteEvent = async (eventId) => {
    try {
      await caseService.updateTimelineEvent(caseId, eventId, { is_completed: true });
      showNotification('success', 'Timeline event marked as complete');
      if (onUpdate) {
        onUpdate(caseId);
      } else {
        fetchTimeline();
      }
    } catch (err) {
      showNotification('error', 'Failed to update timeline event');
      console.error('Error updating timeline event:', err);
    }
  };
  
  // Handle form submit
  const handleFormSubmit = async (formData) => {
    try {
      if (currentEvent) {
        // Update existing event
        await caseService.updateTimelineEvent(caseId, currentEvent.timeline_event_id, formData);
        showNotification('success', 'Timeline event updated successfully');
      } else {
        // Create new event
        await caseService.addTimelineEvent(caseId, formData);
        showNotification('success', 'Timeline event added successfully');
      }
      
      setIsFormOpen(false);
      
      if (onUpdate) {
        onUpdate(caseId);
      } else {
        fetchTimeline();
      }
    } catch (err) {
      showNotification('error', currentEvent ? 'Failed to update timeline event' : 'Failed to add timeline event');
      console.error('Error saving timeline event:', err);
    }
  };
  
  // Sort timeline events by date
  const sortedEvents = [...timelineEvents].sort((a, b) => {
    return new Date(a.event_date) - new Date(b.event_date);
  });
  
  return (
    <Box>
      <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 3 }}>
        <Typography variant="h5" component="h2">
          <TimelineIcon sx={{ mr: 1, verticalAlign: 'middle' }} />
          Case Timeline
        </Typography>
        
        {isEditable && (
          <Button
            variant="contained"
            color="primary"
            startIcon={<AddIcon />}
            onClick={handleAddEvent}
          >
            Add Event
          </Button>
        )}
      </Box>
      
      {loading ? (
        <Box sx={{ display: 'flex', justifyContent: 'center', my: 4 }}>
          <CircularProgress />
        </Box>
      ) : error ? (
        <Alert severity="error" sx={{ mb: 3 }}>{error}</Alert>
      ) : sortedEvents.length === 0 ? (
        <Alert severity="info" sx={{ mb: 3 }}>No timeline events found. Add your first event to start tracking this case's progress.</Alert>
      ) : (
        <Box sx={{ position: 'relative', ml: 2 }}>
          {/* Timeline line */}
          <Box 
            sx={{ 
              position: 'absolute', 
              left: 0, 
              top: 0, 
              bottom: 0, 
              width: 2, 
              backgroundColor: 'divider',
              ml: -10
            }} 
          />
          
          {/* Timeline events */}
          {sortedEvents.map((event) => (
            <Box key={event.timeline_event_id} sx={{ position: 'relative', mb: 4, ml: 3 }}>
              {/* Timeline dot */}
              <Box 
                sx={{ 
                  position: 'absolute', 
                  left: -16, 
                  top: 24, 
                  width: 12, 
                  height: 12, 
                  borderRadius: '50%', 
                  backgroundColor: event.is_completed ? 'success.main' : 'primary.main',
                  ml: -10
                }} 
              />
              
              <TimelineEvent 
                event={event} 
                onEdit={handleEditEvent}
                onDelete={handleDeleteEvent}
                onComplete={handleCompleteEvent}
                isEditable={isEditable}
              />
            </Box>
          ))}
        </Box>
      )}
      
      {/* Timeline Event Form Dialog */}
      <TimelineEventForm
        open={isFormOpen}
        event={currentEvent}
        onClose={() => setIsFormOpen(false)}
        onSubmit={handleFormSubmit}
      />
    </Box>
  );
};

CaseTimeline.propTypes = {
  caseId: PropTypes.oneOfType([PropTypes.string, PropTypes.number]).isRequired,
  timeline: PropTypes.array,
  onUpdate: PropTypes.func
};

export default CaseTimeline;