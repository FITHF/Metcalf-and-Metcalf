import React, { useState, useEffect } from 'react';
import PropTypes from 'prop-types';
import {
  Box,
  Typography,
  Button,
  Paper,
  IconButton,
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions,
  TextField,
  FormControl,
  InputLabel,
  Select,
  MenuItem,
  Grid,
  Chip,
  CircularProgress,
  Alert,
  Tooltip,
  List,
  ListItem,
  ListItemText,
  ListItemSecondaryAction,
  Divider,
  Tab,
  Tabs
} from '@mui/material';
import { 
  Add as AddIcon,
  Edit as EditIcon,
  Delete as DeleteIcon,
  Check as CheckIcon,
  Event as EventIcon,
  Alarm as AlarmIcon,
  AccessTime as AccessTimeIcon,
  NotificationsActive as NotificationsActiveIcon,
  Email as EmailIcon,
  Sms as SmsIcon,
  Notifications as NotificationsIcon,
  HighPriority as HighPriorityIcon,
  Flag as FlagIcon
} from '@mui/icons-material';
import { AdapterDateFns } from '@mui/x-date-pickers/AdapterDateFns';
import { LocalizationProvider, DateTimePicker } from '@mui/x-date-pickers';
import { format, parseISO, isAfter, isBefore, addDays } from 'date-fns';

// Import services
import { useAuth } from '../contexts/AuthContext';
import { useNotification } from '../contexts/NotificationContext';
import caseService from '../services/caseService';

// Reminder Item component
const ReminderItem = ({ reminder, onDelete }) => {
  const reminderTime = parseISO(reminder.reminder_time);
  
  // Get icon based on reminder type
  const getReminderIcon = (type) => {
    switch (type) {
      case 'email':
        return <EmailIcon fontSize="small" />;
      case 'sms':
        return <SmsIcon fontSize="small" />;
      case 'notification':
        return <NotificationsIcon fontSize="small" />;
      default:
        return <AlarmIcon fontSize="small" />;
    }
  };
  
  return (
    <ListItem>
      <ListItemText
        primary={
          <Box sx={{ display: 'flex', alignItems: 'center' }}>
            {getReminderIcon(reminder.reminder_type)}
            <Typography variant="body2" sx={{ ml: 1 }}>
              {format(reminderTime, 'MMM d, yyyy h:mm a')}
            </Typography>
          </Box>
        }
        secondary={`${reminder.reminder_type} reminder ${reminder.is_sent ? '(sent)' : ''}`}
      />
      {onDelete && (
        <ListItemSecondaryAction>
          <IconButton edge="end" size="small" onClick={() => onDelete(reminder.reminder_id)}>
            <DeleteIcon fontSize="small" />
          </IconButton>
        </ListItemSecondaryAction>
      )}
    </ListItem>
  );
};

// Deadline Form Dialog
const DeadlineForm = ({ open, deadline, onClose, onSubmit }) => {
  const [formData, setFormData] = useState({
    title: '',
    description: '',
    deadline_date: new Date(),
    priority: 'normal',
    assigned_to: '',
    reminders: []
  });
  const [errors, setErrors] = useState({});
  const [reminderDate, setReminderDate] = useState(new Date());
  const [reminderType, setReminderType] = useState('notification');
  
  // Initialize form with deadline data if editing
  useEffect(() => {
    if (deadline) {
      setFormData({
        title: deadline.title || '',
        description: deadline.description || '',
        deadline_date: deadline.deadline_date ? parseISO(deadline.deadline_date) : new Date(),
        priority: deadline.priority || 'normal',
        assigned_to: deadline.assigned_to || '',
        reminders: deadline.reminders || []
      });
    } else {
      setFormData({
        title: '',
        description: '',
        deadline_date: new Date(),
        priority: 'normal',
        assigned_to: '',
        reminders: []
      });
    }
    setErrors({});
  }, [deadline, open]);
  
  // Handle form input changes
  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
    
    // Clear error for this field
    if (errors[name]) {
      setErrors(prev => ({ ...prev, [name]: null }));
    }
  };
  
  // Handle deadline date change
  const handleDateChange = (date) => {
    setFormData(prev => ({ ...prev, deadline_date: date }));
    
    // Clear error for date field
    if (errors.deadline_date) {
      setErrors(prev => ({ ...prev, deadline_date: null }));
    }
  };
  
  // Handle reminder date change
  const handleReminderDateChange = (date) => {
    setReminderDate(date);
  };
  
  // Handle reminder type change
  const handleReminderTypeChange = (e) => {
    setReminderType(e.target.value);
  };
  
  // Add reminder
  const handleAddReminder = () => {
    // Validate reminder date is before deadline
    if (isAfter(reminderDate, formData.deadline_date)) {
      setErrors(prev => ({ 
        ...prev, 
        reminder_date: 'Reminder must be before the deadline' 
      }));
      return;
    }
    
    const newReminder = {
      id: Date.now(), // Temporary ID for UI
      time: reminderDate,
      type: reminderType
    };
    
    setFormData(prev => ({
      ...prev,
      reminders: [...prev.reminders, newReminder]
    }));
    
    // Reset reminder form
    setReminderDate(new Date());
    setReminderType('notification');
    setErrors(prev => ({ ...prev, reminder_date: null }));
  };
  
  // Remove reminder
  const handleRemoveReminder = (id) => {
    setFormData(prev => ({
      ...prev,
      reminders: prev.reminders.filter(r => r.id !== id && r.reminder_id !== id)
    }));
  };
  
  // Validate form
  const validateForm = () => {
    const newErrors = {};
    
    if (!formData.title.trim()) {
      newErrors.title = 'Title is required';
    }
    
    if (!formData.deadline_date) {
      newErrors.deadline_date = 'Deadline date is required';
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
    <Dialog open={open} onClose={onClose} maxWidth="md" fullWidth>
      <DialogTitle>
        {deadline ? 'Edit Deadline' : 'Add Deadline'}
      </DialogTitle>
      <DialogContent>
        <Box sx={{ pt: 1 }}>
          <Grid container spacing={2}>
            <Grid item xs={12}>
              <TextField
                name="title"
                label="Deadline Title"
                value={formData.title}
                onChange={handleChange}
                fullWidth
                margin="normal"
                error={Boolean(errors.title)}
                helperText={errors.title}
                required
              />
            </Grid>
            
            <Grid item xs={12} md={6}>
              <LocalizationProvider dateAdapter={AdapterDateFns}>
                <DateTimePicker
                  label="Deadline Date & Time"
                  value={formData.deadline_date}
                  onChange={handleDateChange}
                  renderInput={(params) => (
                    <TextField
                      {...params}
                      fullWidth
                      margin="normal"
                      error={Boolean(errors.deadline_date)}
                      helperText={errors.deadline_date}
                      required
                    />
                  )}
                />
              </LocalizationProvider>
            </Grid>
            
            <Grid item xs={12} md={6}>
              <FormControl fullWidth margin="normal">
                <InputLabel id="priority-label">Priority</InputLabel>
                <Select
                  labelId="priority-label"
                  name="priority"
                  value={formData.priority}
                  onChange={handleChange}
                  label="Priority"
                >
                  <MenuItem value="low">Low</MenuItem>
                  <MenuItem value="normal">Normal</MenuItem>
                  <MenuItem value="high">High</MenuItem>
                  <MenuItem value="urgent">Urgent</MenuItem>
                </Select>
              </FormControl>
            </Grid>
            
            <Grid item xs={12}>
              <TextField
                name="description"
                label="Description"
                value={formData.description}
                onChange={handleChange}
                fullWidth
                margin="normal"
                multiline
                rows={3}
              />
            </Grid>
            
            <Grid item xs={12}>
              <TextField
                name="assigned_to"
                label="Assigned To (User ID)"
                value={formData.assigned_to}
                onChange={handleChange}
                fullWidth
                margin="normal"
                type="number"
              />
            </Grid>
            
            <Grid item xs={12}>
              <Typography variant="h6" sx={{ mt: 2, mb: 1 }}>
                Reminders
              </Typography>
              <Divider sx={{ mb: 2 }} />
              
              <Grid container spacing={2} alignItems="center">
                <Grid item xs={12} md={5}>
                  <LocalizationProvider dateAdapter={AdapterDateFns}>
                    <DateTimePicker
                      label="Reminder Date & Time"
                      value={reminderDate}
                      onChange={handleReminderDateChange}
                      renderInput={(params) => (
                        <TextField
                          {...params}
                          fullWidth
                          margin="normal"
                          error={Boolean(errors.reminder_date)}
                          helperText={errors.reminder_date}
                        />
                      )}
                    />
                  </LocalizationProvider>
                </Grid>
                
                <Grid item xs={12} md={5}>
                  <FormControl fullWidth margin="normal">
                    <InputLabel id="reminder-type-label">Reminder Type</InputLabel>
                    <Select
                      labelId="reminder-type-label"
                      value={reminderType}
                      onChange={handleReminderTypeChange}
                      label="Reminder Type"
                    >
                      <MenuItem value="notification">In-App Notification</MenuItem>
                      <MenuItem value="email">Email</MenuItem>
                      <MenuItem value="sms">SMS</MenuItem>
                    </Select>
                  </FormControl>
                </Grid>
                
                <Grid item xs={12} md={2}>
                  <Button
                    variant="contained"
                    color="primary"
                    onClick={handleAddReminder}
                    fullWidth
                    sx={{ mt: 2 }}
                  >
                    Add
                  </Button>
                </Grid>
              </Grid>
              
              {formData.reminders.length > 0 ? (
                <List sx={{ mt: 2 }}>
                  {formData.reminders.map((reminder) => (
                    <React.Fragment key={reminder.id || reminder.reminder_id}>
                      <ListItem>
                        <ListItemText
                          primary={
                            <Box sx={{ display: 'flex', alignItems: 'center' }}>
                              {reminder.type === 'email' ? <EmailIcon fontSize="small" /> :
                               reminder.type === 'sms' ? <SmsIcon fontSize="small" /> :
                               <NotificationsIcon fontSize="small" />}
                              <Typography variant="body2" sx={{ ml: 1 }}>
                                {format(new Date(reminder.time), 'MMM d, yyyy h:mm a')}
                              </Typography>
                            </Box>
                          }
                          secondary={`${reminder.type} reminder`}
                        />
                        <ListItemSecondaryAction>
                          <IconButton edge="end" onClick={() => handleRemoveReminder(reminder.id || reminder.reminder_id)}>
                            <DeleteIcon />
                          </IconButton>
                        </ListItemSecondaryAction>
                      </ListItem>
                      <Divider />
                    </React.Fragment>
                  ))}
                </List>
              ) : (
                <Alert severity="info" sx={{ mt: 2 }}>
                  No reminders added yet. Add reminders to be notified before the deadline.
                </Alert>
              )}
            </Grid>
          </Grid>
        </Box>
      </DialogContent>
      <DialogActions>
        <Button onClick={onClose}>Cancel</Button>
        <Button onClick={handleSubmit} variant="contained" color="primary">
          {deadline ? 'Update' : 'Add'}
        </Button>
      </DialogActions>
    </Dialog>
  );
};

// Deadline Item component
const DeadlineItem = ({ deadline, onEdit, onDelete, onComplete, isEditable }) => {
  const deadlineDate = parseISO(deadline.deadline_date);
  const isPast = isBefore(deadlineDate, new Date());
  const isCompleted = deadline.status === 'completed';
  
  // Get priority color
  const getPriorityColor = (priority) => {
    switch (priority) {
      case 'low':
        return 'info';
      case 'normal':
        return 'primary';
      case 'high':
        return 'warning';
      case 'urgent':
        return 'error';
      default:
        return 'primary';
    }
  };
  
  // Get status color
  const getStatusColor = (status) => {
    switch (status) {
      case 'pending':
        return isPast ? 'error' : 'primary';
      case 'completed':
        return 'success';
      case 'missed':
        return 'error';
      default:
        return 'primary';
    }
  };
  
  return (
    <Paper 
      elevation={1} 
      sx={{ 
        p: 2, 
        mb: 2, 
        borderLeft: 4, 
        borderColor: `${getStatusColor(deadline.status)}.main`,
        backgroundColor: isCompleted ? 'success.light' : (isPast && !isCompleted ? 'error.light' : 'white'),
        opacity: isCompleted ? 0.9 : 1
      }}
    >
      <Grid container spacing={2}>
        <Grid item xs={12} sm={8}>
          <Typography variant="h6" component="h3">
            {deadline.title}
            <Chip 
              label={deadline.priority} 
              color={getPriorityColor(deadline.priority)} 
              size="small" 
              sx={{ ml: 1, textTransform: 'capitalize' }} 
            />
          </Typography>
          
          <Typography variant="body2" color="text.secondary" gutterBottom>
            <AccessTimeIcon fontSize="small" sx={{ mr: 0.5, verticalAlign: 'middle' }} />
            {format(deadlineDate, 'MMMM d, yyyy h:mm a')}
            {isPast && !isCompleted && (
              <Chip 
                label="Overdue" 
                color="error" 
                size="small" 
                sx={{ ml: 1 }} 
              />
            )}
          </Typography>
          
          {deadline.description && (
            <Typography variant="body1" sx={{ mt: 1 }}>
              {deadline.description}
            </Typography>
          )}
          
          {deadline.assigned_to_name && (
            <Typography variant="body2" color="text.secondary" sx={{ mt: 1 }}>
              Assigned to: {deadline.assigned_to_name}
            </Typography>
          )}
          
          {deadline.reminders && deadline.reminders.length > 0 && (
            <Box sx={{ mt: 2 }}>
              <Typography variant="body2" color="text.secondary">
                <NotificationsActiveIcon fontSize="small" sx={{ mr: 0.5, verticalAlign: 'middle' }} />
                Reminders:
              </Typography>
              <List dense>
                {deadline.reminders.map((reminder) => (
                  <ReminderItem key={reminder.reminder_id} reminder={reminder} />
                ))}
              </List>
            </Box>
          )}
        </Grid>
        
        <Grid item xs={12} sm={4} sx={{ textAlign: 'right' }}>
          {isEditable && (
            <>
              {!isCompleted && (
                <Tooltip title="Mark as Complete">
                  <IconButton 
                    color="success" 
                    onClick={() => onComplete(deadline.deadline_id)}
                    size="small"
                    sx={{ mr: 1 }}
                  >
                    <CheckIcon />
                  </IconButton>
                </Tooltip>
              )}
              <Tooltip title="Edit Deadline">
                <IconButton 
                  color="primary" 
                  onClick={() => onEdit(deadline)}
                  size="small"
                  sx={{ mr: 1 }}
                >
                  <EditIcon />
                </IconButton>
              </Tooltip>
              <Tooltip title="Delete Deadline">
                <IconButton 
                  color="error" 
                  onClick={() => onDelete(deadline.deadline_id)}
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

// Main CaseDeadlines component
const CaseDeadlines = ({ caseId, deadlines = [], onUpdate }) => {
  const { user } = useAuth();
  const { showNotification } = useNotification();
  
  // State
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [caseDeadlines, setCaseDeadlines] = useState([]);
  const [isFormOpen, setIsFormOpen] = useState(false);
  const [currentDeadline, setCurrentDeadline] = useState(null);
  const [statusFilter, setStatusFilter] = useState('all');
  
  // Check if user can edit deadlines
  const isEditable = user?.roles?.includes('law_firm_staff') || user?.roles?.includes('law_firm_admin');
  
  // Initialize deadlines from props
  useEffect(() => {
    if (deadlines && deadlines.length > 0) {
      setCaseDeadlines(deadlines);
    } else {
      fetchDeadlines();
    }
  }, [deadlines, caseId]);
  
  // Fetch deadlines
  const fetchDeadlines = async (status = 'all') => {
    if (!caseId) return;
    
    setLoading(true);
    try {
      const response = await caseService.getCaseDeadlines(caseId, status);
      setCaseDeadlines(response.data);
      setError(null);
    } catch (err) {
      setError('Failed to fetch deadlines. Please try again.');
      showNotification('error', 'Failed to fetch deadlines');
      console.error('Error fetching deadlines:', err);
    } finally {
      setLoading(false);
    }
  };
  
  // Handle status filter change
  const handleStatusFilterChange = (event, newValue) => {
    setStatusFilter(newValue);
    fetchDeadlines(newValue);
  };
  
  // Handle add deadline
  const handleAddDeadline = () => {
    setCurrentDeadline(null);
    setIsFormOpen(true);
  };
  
  // Handle edit deadline
  const handleEditDeadline = (deadline) => {
    setCurrentDeadline(deadline);
    setIsFormOpen(true);
  };
  
  // Handle delete deadline
  const handleDeleteDeadline = async (deadlineId) => {
    if (window.confirm('Are you sure you want to delete this deadline? This action cannot be undone.')) {
      try {
        await caseService.deleteCaseDeadline(caseId, deadlineId);
        showNotification('success', 'Deadline deleted successfully');
        if (onUpdate) {
          onUpdate(caseId);
        } else {
          fetchDeadlines(statusFilter);
        }
      } catch (err) {
        showNotification('error', 'Failed to delete deadline');
        console.error('Error deleting deadline:', err);
      }
    }
  };
  
  // Handle complete deadline
  const handleCompleteDeadline = async (deadlineId) => {
    try {
      await caseService.completeDeadline(caseId, deadlineId);
      showNotification('success', 'Deadline marked as complete');
      if (onUpdate) {
        onUpdate(caseId);
      } else {
        fetchDeadlines(statusFilter);
      }
    } catch (err) {
      showNotification('error', 'Failed to update deadline');
      console.error('Error updating deadline:', err);
    }
  };
  
  // Handle form submit
  const handleFormSubmit = async (formData) => {
    try {
      if (currentDeadline) {
        // Update existing deadline
        await caseService.updateCaseDeadline(caseId, currentDeadline.deadline_id, formData);
        showNotification('success', 'Deadline updated successfully');
      } else {
        // Create new deadline
        await caseService.addCaseDeadline(caseId, formData);
        showNotification('success', 'Deadline added successfully');
      }
      
      setIsFormOpen(false);
      
      if (onUpdate) {
        onUpdate(caseId);
      } else {
        fetchDeadlines(statusFilter);
      }
    } catch (err) {
      showNotification('error', currentDeadline ? 'Failed to update deadline' : 'Failed to add deadline');
      console.error('Error saving deadline:', err);
    }
  };
  
  // Filter deadlines by status
  const getFilteredDeadlines = () => {
    if (statusFilter === 'all') {
      return caseDeadlines;
    }
    return caseDeadlines.filter(deadline => deadline.status === statusFilter);
  };
  
  // Sort deadlines by date
  const sortedDeadlines = getFilteredDeadlines().sort((a, b) => {
    return new Date(a.deadline_date) - new Date(b.deadline_date);
  });
  
  // Group deadlines by status
  const groupedDeadlines = {
    upcoming: sortedDeadlines.filter(d => 
      d.status === 'pending' && 
      isAfter(parseISO(d.deadline_date), new Date())
    ),
    overdue: sortedDeadlines.filter(d => 
      d.status === 'pending' && 
      isBefore(parseISO(d.deadline_date), new Date())
    ),
    completed: sortedDeadlines.filter(d => d.status === 'completed')
  };
  
  return (
    <Box>
      <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 3 }}>
        <Typography variant="h5" component="h2">
          <EventIcon sx={{ mr: 1, verticalAlign: 'middle' }} />
          Case Deadlines
        </Typography>
        
        {isEditable && (
          <Button
            variant="contained"
            color="primary"
            startIcon={<AddIcon />}
            onClick={handleAddDeadline}
          >
            Add Deadline
          </Button>
        )}
      </Box>
      
      <Box sx={{ borderBottom: 1, borderColor: 'divider', mb: 3 }}>
        <Tabs 
          value={statusFilter} 
          onChange={handleStatusFilterChange}
          aria-label="deadline status tabs"
        >
          <Tab label="All" value="all" />
          <Tab label="Pending" value="pending" />
          <Tab label="Completed" value="completed" />
        </Tabs>
      </Box>
      
      {loading ? (
        <Box sx={{ display: 'flex', justifyContent: 'center', my: 4 }}>
          <CircularProgress />
        </Box>
      ) : error ? (
        <Alert severity="error" sx={{ mb: 3 }}>{error}</Alert>
      ) : sortedDeadlines.length === 0 ? (
        <Alert severity="info" sx={{ mb: 3 }}>
          No deadlines found for the selected status. 
          {isEditable && ' Add your first deadline to start tracking important dates for this case.'}
        </Alert>
      ) : (
        <Box>
          {statusFilter === 'all' && (
            <>
              {groupedDeadlines.overdue.length > 0 && (
                <Box sx={{ mb: 4 }}>
                  <Typography variant="h6" color="error" sx={{ mb: 2 }}>
                    <FlagIcon sx={{ mr: 1, verticalAlign: 'middle' }} />
                    Overdue ({groupedDeadlines.overdue.length})
                  </Typography>
                  {groupedDeadlines.overdue.map((deadline) => (
                    <DeadlineItem
                      key={deadline.deadline_id}
                      deadline={deadline}
                      onEdit={handleEditDeadline}
                      onDelete={handleDeleteDeadline}
                      onComplete={handleCompleteDeadline}
                      isEditable={isEditable}
                    />
                  ))}
                </Box>
              )}
              
              {groupedDeadlines.upcoming.length > 0 && (
                <Box sx={{ mb: 4 }}>
                  <Typography variant="h6" color="primary" sx={{ mb: 2 }}>
                    <AccessTimeIcon sx={{ mr: 1, verticalAlign: 'middle' }} />
                    Upcoming ({groupedDeadlines.upcoming.length})
                  </Typography>
                  {groupedDeadlines.upcoming.map((deadline) => (
                    <DeadlineItem
                      key={deadline.deadline_id}
                      deadline={deadline}
                      onEdit={handleEditDeadline}
                      onDelete={handleDeleteDeadline}
                      onComplete={handleCompleteDeadline}
                      isEditable={isEditable}
                    />
                  ))}
                </Box>
              )}
              
              {groupedDeadlines.completed.length > 0 && (
                <Box sx={{ mb: 4 }}>
                  <Typography variant="h6" color="success" sx={{ mb: 2 }}>
                    <CheckIcon sx={{ mr: 1, verticalAlign: 'middle' }} />
                    Completed ({groupedDeadlines.completed.length})
                  </Typography>
                  {groupedDeadlines.completed.map((deadline) => (
                    <DeadlineItem
                      key={deadline.deadline_id}
                      deadline={deadline}
                      onEdit={handleEditDeadline}
                      onDelete={handleDeleteDeadline}
                      onComplete={handleCompleteDeadline}
                      isEditable={isEditable}
                    />
                  ))}
                </Box>
              )}
            </>
          ) : (
            <Box>
              {sortedDeadlines.map((deadline) => (
                <DeadlineItem
                  key={deadline.deadline_id}
                  deadline={deadline}
                  onEdit={handleEditDeadline}
                  onDelete={handleDeleteDeadline}
                  onComplete={handleCompleteDeadline}
                  isEditable={isEditable}
                />
              ))}
            </Box>
          )}
        </Box>
      )}
      
      {/* Deadline Form Dialog */}
      <DeadlineForm
        open={isFormOpen}
        deadline={currentDeadline}
        onClose={() => setIsFormOpen(false)}
        onSubmit={handleFormSubmit}
      />
    </Box>
  );
};

CaseDeadlines.propTypes = {
  caseId: PropTypes.oneOfType([PropTypes.string, PropTypes.number]).isRequired,
  deadlines: PropTypes.array,
  onUpdate: PropTypes.func
};

export default CaseDeadlines;