import React, { useState, useEffect } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { 
  Box, 
  Container, 
  Grid, 
  Typography, 
  Paper, 
  Button, 
  Tabs, 
  Tab,
  CircularProgress,
  Alert,
  Breadcrumbs,
  Link
} from '@mui/material';
import { 
  Add as AddIcon,
  Edit as EditIcon,
  Delete as DeleteIcon,
  Refresh as RefreshIcon
} from '@mui/icons-material';

// Import components
import CaseList from './components/Cases/CaseList';
import CaseDetails from './components/Cases/CaseDetails';
import CaseTimeline from './components/Cases/CaseTimeline';
import CaseTeam from './components/Cases/CaseTeam';
import CaseNotes from './components/Cases/CaseNotes';
import CaseDeadlines from './components/Cases/CaseDeadlines';
import CaseForm from './components/Cases/CaseForm';
import CaseStatusHistory from './components/Cases/CaseStatusHistory';
import CaseFilter from './components/Cases/CaseFilter';

// Import services
import { useAuth } from '../contexts/AuthContext';
import { useNotification } from '../contexts/NotificationContext';
import caseService from '../services/caseService';

// TabPanel component for tab content
function TabPanel(props) {
  const { children, value, index, ...other } = props;

  return (
    <div
      role="tabpanel"
      hidden={value !== index}
      id={`case-tabpanel-${index}`}
      aria-labelledby={`case-tab-${index}`}
      {...other}
    >
      {value === index && (
        <Box sx={{ p: 3 }}>
          {children}
        </Box>
      )}
    </div>
  );
}

const CaseManagement = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const { user } = useAuth();
  const { showNotification } = useNotification();
  
  // State
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [cases, setCases] = useState([]);
  const [currentCase, setCurrentCase] = useState(null);
  const [tabValue, setTabValue] = useState(0);
  const [isFormOpen, setIsFormOpen] = useState(false);
  const [formMode, setFormMode] = useState('create');
  const [filters, setFilters] = useState({
    status: '',
    category: '',
    type: '',
    search: '',
  });
  
  // Reference data
  const [categories, setCategories] = useState([]);
  const [types, setTypes] = useState([]);
  const [statuses, setStatuses] = useState([]);
  
  // Fetch cases on component mount
  useEffect(() => {
    fetchCases();
    fetchReferenceData();
  }, []);
  
  // Fetch case details when ID changes
  useEffect(() => {
    if (id) {
      fetchCaseDetails(id);
    } else {
      setCurrentCase(null);
      setTabValue(0);
    }
  }, [id]);
  
  // Fetch all cases
  const fetchCases = async () => {
    setLoading(true);
    try {
      const response = await caseService.getCases(filters);
      setCases(response.data);
      setError(null);
    } catch (err) {
      setError('Failed to fetch cases. Please try again.');
      showNotification('error', 'Failed to fetch cases');
      console.error('Error fetching cases:', err);
    } finally {
      setLoading(false);
    }
  };
  
  // Fetch reference data (categories, types, statuses)
  const fetchReferenceData = async () => {
    try {
      const [categoriesRes, typesRes, statusesRes] = await Promise.all([
        caseService.getCategories(),
        caseService.getTypes(),
        caseService.getStatuses()
      ]);
      
      setCategories(categoriesRes.data);
      setTypes(typesRes.data);
      setStatuses(statusesRes.data);
    } catch (err) {
      console.error('Error fetching reference data:', err);
    }
  };
  
  // Fetch case details
  const fetchCaseDetails = async (caseId) => {
    setLoading(true);
    try {
      const response = await caseService.getCase(caseId);
      setCurrentCase(response.data);
      setError(null);
    } catch (err) {
      setError('Failed to fetch case details. Please try again.');
      showNotification('error', 'Failed to fetch case details');
      console.error('Error fetching case details:', err);
      navigate('/cases');
    } finally {
      setLoading(false);
    }
  };
  
  // Handle tab change
  const handleTabChange = (event, newValue) => {
    setTabValue(newValue);
  };
  
  // Handle case selection
  const handleCaseSelect = (caseId) => {
    navigate(`/cases/${caseId}`);
  };
  
  // Handle create case
  const handleCreateCase = () => {
    setFormMode('create');
    setIsFormOpen(true);
  };
  
  // Handle edit case
  const handleEditCase = () => {
    setFormMode('edit');
    setIsFormOpen(true);
  };
  
  // Handle delete case
  const handleDeleteCase = async () => {
    if (!currentCase) return;
    
    if (window.confirm('Are you sure you want to delete this case? This action cannot be undone.')) {
      try {
        await caseService.deleteCase(currentCase.case_id);
        showNotification('success', 'Case deleted successfully');
        navigate('/cases');
        fetchCases();
      } catch (err) {
        showNotification('error', 'Failed to delete case');
        console.error('Error deleting case:', err);
      }
    }
  };
  
  // Handle form submit
  const handleFormSubmit = async (caseData) => {
    try {
      if (formMode === 'create') {
        const response = await caseService.createCase(caseData);
        showNotification('success', 'Case created successfully');
        navigate(`/cases/${response.data.case_id}`);
      } else {
        await caseService.updateCase(currentCase.case_id, caseData);
        showNotification('success', 'Case updated successfully');
        fetchCaseDetails(currentCase.case_id);
      }
      
      setIsFormOpen(false);
      fetchCases();
    } catch (err) {
      showNotification('error', `Failed to ${formMode} case`);
      console.error(`Error ${formMode}ing case:`, err);
    }
  };
  
  // Handle form cancel
  const handleFormCancel = () => {
    setIsFormOpen(false);
  };
  
  // Handle filter change
  const handleFilterChange = (newFilters) => {
    setFilters(newFilters);
  };
  
  // Handle filter apply
  const handleFilterApply = () => {
    fetchCases();
  };
  
  // Handle refresh
  const handleRefresh = () => {
    if (currentCase) {
      fetchCaseDetails(currentCase.case_id);
    } else {
      fetchCases();
    }
  };
  
  // Render case list view
  const renderCaseList = () => {
    return (
      <Container maxWidth="lg">
        <Box sx={{ mb: 4 }}>
          <Grid container spacing={2} alignItems="center" justifyContent="space-between">
            <Grid item>
              <Typography variant="h4" component="h1" gutterBottom>
                Case Management
              </Typography>
            </Grid>
            <Grid item>
              <Button
                variant="contained"
                color="primary"
                startIcon={<AddIcon />}
                onClick={handleCreateCase}
                sx={{ mr: 1 }}
              >
                New Case
              </Button>
              <Button
                variant="outlined"
                startIcon={<RefreshIcon />}
                onClick={handleRefresh}
              >
                Refresh
              </Button>
            </Grid>
          </Grid>
        </Box>
        
        <Paper sx={{ mb: 3, p: 2 }}>
          <CaseFilter 
            filters={filters} 
            categories={categories}
            types={types}
            statuses={statuses}
            onChange={handleFilterChange}
            onApply={handleFilterApply}
          />
        </Paper>
        
        {loading ? (
          <Box sx={{ display: 'flex', justifyContent: 'center', my: 4 }}>
            <CircularProgress />
          </Box>
        ) : error ? (
          <Alert severity="error" sx={{ mb: 3 }}>{error}</Alert>
        ) : (
          <CaseList 
            cases={cases} 
            onSelect={handleCaseSelect} 
            categories={categories}
            types={types}
            statuses={statuses}
          />
        )}
      </Container>
    );
  };
  
  // Render case detail view
  const renderCaseDetail = () => {
    if (!currentCase) return null;
    
    return (
      <Container maxWidth="lg">
        <Box sx={{ mb: 4 }}>
          <Breadcrumbs aria-label="breadcrumb" sx={{ mb: 2 }}>
            <Link color="inherit" onClick={() => navigate('/cases')} sx={{ cursor: 'pointer' }}>
              Cases
            </Link>
            <Typography color="text.primary">{currentCase.case_title}</Typography>
          </Breadcrumbs>
          
          <Grid container spacing={2} alignItems="center" justifyContent="space-between">
            <Grid item>
              <Typography variant="h4" component="h1" gutterBottom>
                {currentCase.case_title}
              </Typography>
              <Typography variant="subtitle1" color="text.secondary">
                Case #{currentCase.case_id} • {currentCase.case_status}
              </Typography>
            </Grid>
            <Grid item>
              <Button
                variant="outlined"
                color="primary"
                startIcon={<EditIcon />}
                onClick={handleEditCase}
                sx={{ mr: 1 }}
              >
                Edit
              </Button>
              {user?.roles?.includes('law_firm_admin') && (
                <Button
                  variant="outlined"
                  color="error"
                  startIcon={<DeleteIcon />}
                  onClick={handleDeleteCase}
                  sx={{ mr: 1 }}
                >
                  Delete
                </Button>
              )}
              <Button
                variant="outlined"
                startIcon={<RefreshIcon />}
                onClick={handleRefresh}
              >
                Refresh
              </Button>
            </Grid>
          </Grid>
        </Box>
        
        {loading ? (
          <Box sx={{ display: 'flex', justifyContent: 'center', my: 4 }}>
            <CircularProgress />
          </Box>
        ) : error ? (
          <Alert severity="error" sx={{ mb: 3 }}>{error}</Alert>
        ) : (
          <>
            <Paper sx={{ mb: 3 }}>
              <Tabs 
                value={tabValue} 
                onChange={handleTabChange}
                variant="scrollable"
                scrollButtons="auto"
              >
                <Tab label="Overview" />
                <Tab label="Timeline" />
                <Tab label="Team" />
                <Tab label="Notes" />
                <Tab label="Deadlines" />
                <Tab label="Status History" />
              </Tabs>
              
              <TabPanel value={tabValue} index={0}>
                <CaseDetails case={currentCase} />
              </TabPanel>
              
              <TabPanel value={tabValue} index={1}>
                <CaseTimeline 
                  caseId={currentCase.case_id} 
                  timeline={currentCase.timeline} 
                  onUpdate={fetchCaseDetails}
                />
              </TabPanel>
              
              <TabPanel value={tabValue} index={2}>
                <CaseTeam 
                  caseId={currentCase.case_id} 
                  team={currentCase.team} 
                  onUpdate={fetchCaseDetails}
                />
              </TabPanel>
              
              <TabPanel value={tabValue} index={3}>
                <CaseNotes 
                  caseId={currentCase.case_id} 
                  notes={currentCase.notes} 
                  onUpdate={fetchCaseDetails}
                />
              </TabPanel>
              
              <TabPanel value={tabValue} index={4}>
                <CaseDeadlines 
                  caseId={currentCase.case_id} 
                  deadlines={currentCase.deadlines} 
                  onUpdate={fetchCaseDetails}
                />
              </TabPanel>
              
              <TabPanel value={tabValue} index={5}>
                <CaseStatusHistory 
                  caseId={currentCase.case_id} 
                  statusInfo={currentCase.status_info} 
                  statuses={statuses}
                  onUpdate={fetchCaseDetails}
                />
              </TabPanel>
            </Paper>
          </>
        )}
      </Container>
    );
  };
  
  return (
    <>
      {currentCase ? renderCaseDetail() : renderCaseList()}
      
      {isFormOpen && (
        <CaseForm
          open={isFormOpen}
          mode={formMode}
          caseData={formMode === 'edit' ? currentCase : null}
          categories={categories}
          types={types}
          statuses={statuses}
          onSubmit={handleFormSubmit}
          onCancel={handleFormCancel}
        />
      )}
    </>
  );
};

export default CaseManagement;