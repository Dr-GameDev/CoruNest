// ===== REACT ADMIN DASHBOARD (resources/js/components/AdminDashboard.jsx) =====
import React, { useState, useEffect } from 'react';
import { Line, Bar, Doughnut } from 'react-chartjs-2';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  ArcElement,
  Title,
  Tooltip,
  Legend,
} from 'chart.js';

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  ArcElement,
  Title,
  Tooltip,
  Legend
);

// Dashboard Layout Component
export const DashboardLayout = ({ children, currentPage = 'dashboard' }) => {
  const [sidebarOpen, setSidebarOpen] = useState(false);

  const menuItems = [
    { id: 'dashboard', label: 'Dashboard', icon: 'fas fa-tachometer-alt', href: '/admin' },
    { id: 'campaigns', label: 'Campaigns', icon: 'fas fa-bullhorn', href: '/admin/campaigns' },
    { id: 'donations', label: 'Donations', icon: 'fas fa-hand-holding-heart', href: '/admin/donations' },
    { id: 'events', label: 'Events', icon: 'fas fa-calendar-alt', href: '/admin/events' },
    { id: 'volunteers', label: 'Volunteers', icon: 'fas fa-users', href: '/admin/volunteers' },
    { id: 'analytics', label: 'Analytics', icon: 'fas fa-chart-line', href: '/admin/analytics' },
    { id: 'settings', label: 'Settings', icon: 'fas fa-cog', href: '/admin/settings' },
  ];

  return (
    <div className="dashboard-layout">
      <aside className={`sidebar ${sidebarOpen ? 'open' : ''}`}>
        <div className="sidebar-logo">
          <i className="fas fa-heart"></i> CoruNest
        </div>
        <nav>
          <ul className="sidebar-nav">
            {menuItems.map((item) => (
              <li key={item.id}>
                <a 
                  href={item.href} 
                  className={currentPage === item.id ? 'active' : ''}
                >
                  <i className={item.icon}></i> {item.label}
                </a>
              </li>
            ))}
          </ul>
        </nav>
      </aside>
      
      <main className="main-content">
        <div className="mobile-header md:hidden">
          <button 
            onClick={() => setSidebarOpen(!sidebarOpen)}
            className="mobile-menu-btn"
          >
            <i className="fas fa-bars"></i>
          </button>
          <div className="mobile-logo">CoruNest</div>
        </div>
        {children}
      </main>
    </div>
  );
};

// Dashboard Overview Component
export const DashboardOverview = () => {
  const [stats, setStats] = useState({
    totalRaised: 472000,
    activeCampaigns: 12,
    totalVolunteers: 2840,
    upcomingEvents: 8,
    recentDonations: 45,
    conversionRate: 3.2
  });

  const [donationData, setDonationData] = useState({
    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep'],
    datasets: [
      {
        label: 'Donations (R)',
        data: [45000, 52000, 48000, 61000, 55000, 67000, 73000, 68000, 78000],
        borderColor: 'rgb(16, 185, 129)',
        backgroundColor: 'rgba(16, 185, 129, 0.1)',
        tension: 0.4
      }
    ]
  });

  const campaignPerformance = {
    labels: ['Education', 'Health', 'Environment', 'Community'],
    datasets: [
      {
        label: 'Funds Raised',
        data: [180000, 120000, 95000, 77000],
        backgroundColor: [
          'rgba(59, 130, 246, 0.8)',
          'rgba(16, 185, 129, 0.8)',
          'rgba(245, 158, 11, 0.8)',
          'rgba(139, 92, 246, 0.8)'
        ]
      }
    ]
  };

  const volunteerEngagement = {
    labels: ['Signed Up', 'Confirmed', 'Attended'],
    datasets: [
      {
        data: [2840, 2156, 1923],
        backgroundColor: [
          'rgba(16, 185, 129, 0.8)',
          'rgba(59, 130, 246, 0.8)',
          'rgba(245, 158, 11, 0.8)'
        ]
      }
    ]
  };

  const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        position: 'bottom',
      },
      title: {
        display: false,
      },
    },
    scales: {
      y: {
        beginAtZero: true,
        ticks: {
          callback: function(value) {
            return 'R' + value.toLocaleString();
          }
        }
      }
    }
  };

  return (
    <div className="dashboard-overview">
      <div className="dashboard-header">
        <div>
          <h1 className="page-title">Dashboard Overview</h1>
          <p className="text-gray-600">Welcome back! Here's what's happening with your NGO.</p>
        </div>
        <div className="flex gap-3">
          <button className="btn btn-outline">
            <i className="fas fa-download"></i> Export Report
          </button>
          <button className="btn btn-primary">
            <i className="fas fa-plus"></i> Create Campaign
          </button>
        </div>
      </div>

      {/* KPI Cards */}
      <div className="kpi-grid">
        <div className="kpi-card">
          <div className="kpi-icon" style={{backgroundColor: 'rgba(16, 185, 129, 0.1)', color: '#10b981'}}>
            <i className="fas fa-hand-holding-heart"></i>
          </div>
          <div className="kpi-content">
            <div className="kpi-label">Total Raised</div>
            <div className="kpi-value">R{stats.totalRaised.toLocaleString()}</div>
            <div className="kpi-trend positive">
              <i className="fas fa-arrow-up"></i> +12.5% from last month
            </div>
          </div>
        </div>
        
        <div className="kpi-card">
          <div className="kpi-icon" style={{backgroundColor: 'rgba(59, 130, 246, 0.1)', color: '#3b82f6'}}>
            <i className="fas fa-bullhorn"></i>
          </div>
          <div className="kpi-content">
            <div className="kpi-label">Active Campaigns</div>
            <div className="kpi-value">{stats.activeCampaigns}</div>
            <div className="kpi-trend positive">
              <i className="fas fa-arrow-up"></i> +3 new this week
            </div>
          </div>
        </div>
        
        <div className="kpi-card">
          <div className="kpi-icon" style={{backgroundColor: 'rgba(245, 158, 11, 0.1)', color: '#f59e0b'}}>
            <i className="fas fa-users"></i>
          </div>
          <div className="kpi-content">
            <div className="kpi-label">Active Volunteers</div>
            <div className="kpi-value">{stats.totalVolunteers.toLocaleString()}</div>
            <div className="kpi-trend positive">
              <i className="fas fa-arrow-up"></i> +156 this month
            </div>
          </div>
        </div>
        
        <div className="kpi-card">
          <div className="kpi-icon" style={{backgroundColor: 'rgba(139, 92, 246, 0.1)', color: '#8b5cf6'}}>
            <i className="fas fa-calendar-alt"></i>
          </div>
          <div className="kpi-content">
            <div className="kpi-label">Upcoming Events</div>
            <div className="kpi-value">{stats.upcomingEvents}</div>
            <div className="kpi-trend">
              <i className="fas fa-calendar"></i> Next: Sept 22
            </div>
          </div>
        </div>
      </div>

      {/* Charts Grid */}
      <div className="charts-grid">
        <div className="chart-container large">
          <h3 className="chart-title">Donations Over Time</h3>
          <div style={{height: '300px', position: 'relative'}}>
            <Line data={donationData} options={chartOptions} />
          </div>
        </div>

        <div className="chart-container">
          <h3 className="chart-title">Campaign Performance</h3>
          <div style={{height: '250px', position: 'relative'}}>
            <Bar data={campaignPerformance} options={{
              ...chartOptions,
              plugins: {
                ...chartOptions.plugins,
                legend: { display: false }
              }
            }} />
          </div>
        </div>

        <div className="chart-container">
          <h3 className="chart-title">Volunteer Engagement</h3>
          <div style={{height: '250px', position: 'relative'}}>
            <Doughnut data={volunteerEngagement} options={{
              responsive: true,
              maintainAspectRatio: false,
              plugins: {
                legend: {
                  position: 'bottom',
                },
              },
            }} />
          </div>
        </div>
      </div>

      {/* Recent Activity */}
      <div className="recent-activity">
        <div className="card">
          <div className="card-header">
            <h3>Recent Donations</h3>
            <a href="/admin/donations" className="text-primary">View All</a>
          </div>
          <div className="activity-list">
            {[
              { donor: 'Sarah Johnson', amount: 500, campaign: 'Education for All', time: '2 minutes ago' },
              { donor: 'Michael Chen', amount: 250, campaign: 'Clean Water Initiative', time: '15 minutes ago' },
              { donor: 'Anonymous', amount: 1000, campaign: 'Urban Garden Project', time: '1 hour ago' },
              { donor: 'Lisa Williams', amount: 150, campaign: 'Senior Care Support', time: '2 hours ago' },
            ].map((donation, index) => (
              <div key={index} className="activity-item">
                <div className="activity-icon">
                  <i className="fas fa-heart text-primary"></i>
                </div>
                <div className="activity-content">
                  <div className="activity-title">
                    <strong>{donation.donor}</strong> donated R{donation.amount} to {donation.campaign}
                  </div>
                  <div className="activity-time">{donation.time}</div>
                </div>
                <div className="activity-amount">
                  +R{donation.amount}
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>
    </div>
  );
};

// Campaign Management Component
export const CampaignManagement = () => {
  const [campaigns, setCampaigns] = useState([
    {
      id: 1,
      title: 'Education for All Children',
      status: 'active',
      raised: 125000,
      target: 200000,
      donors: 234,
      createdAt: '2025-08-15',
      endDate: '2025-12-31'
    },
    {
      id: 2,
      title: 'Clean Water Initiative',
      status: 'active',
      raised: 89000,
      target: 150000,
      donors: 189,
      createdAt: '2025-07-20',
      endDate: '2025-11-30'
    },
    {
      id: 3,
      title: 'Urban Garden Project',
      status: 'draft',
      raised: 45000,
      target: 75000,
      donors: 98,
      createdAt: '2025-09-01',
      endDate: '2025-10-31'
    }
  ]);

  const [showCreateModal, setShowCreateModal] = useState(false);
  const [filterStatus, setFilterStatus] = useState('all');
  const [sortBy, setSortBy] = useState('created_desc');

  const getStatusColor = (status) => {
    const colors = {
      active: 'bg-green-100 text-green-800',
      draft: 'bg-yellow-100 text-yellow-800',
      completed: 'bg-blue-100 text-blue-800',
      archived: 'bg-gray-100 text-gray-800'
    };
    return colors[status] || colors.draft;
  };

  const filteredCampaigns = campaigns.filter(campaign => 
    filterStatus === 'all' || campaign.status === filterStatus
  );

  return (
    <div className="campaign-management">
      <div className="dashboard-header">
        <div>
          <h1 className="page-title">Campaign Management</h1>
          <p className="text-gray-600">Create, edit, and monitor your fundraising campaigns</p>
        </div>
        <button 
          className="btn btn-primary"
          onClick={() => setShowCreateModal(true)}
        >
          <i className="fas fa-plus"></i> Create New Campaign
        </button>
      </div>

      {/* Filters and Search */}
      <div className="filters-bar">
        <div className="filter-group">
          <label>Status:</label>
          <select 
            value={filterStatus}
            onChange={(e) => setFilterStatus(e.target.value)}
            className="filter-select"
          >
            <option value="all">All Statuses</option>
            <option value="active">Active</option>
            <option value="draft">Draft</option>
            <option value="completed">Completed</option>
            <option value="archived">Archived</option>
          </select>
        </div>
        
        <div className="filter-group">
          <label>Sort by:</label>
          <select 
            value={sortBy}
            onChange={(e) => setSortBy(e.target.value)}
            className="filter-select"
          >
            <option value="created_desc">Newest First</option>
            <option value="created_asc">Oldest First</option>
            <option value="raised_desc">Highest Raised</option>
            <option value="target_desc">Highest Target</option>
          </select>
        </div>

        <div className="search-group">
          <div className="search-input">
            <i className="fas fa-search"></i>
            <input type="text" placeholder="Search campaigns..." />
          </div>
        </div>
      </div>

      {/* Campaigns Table */}
      <div className="card">
        <div className="table-container">
          <table className="campaigns-table">
            <thead>
              <tr>
                <th>Campaign</th>
                <th>Status</th>
                <th>Progress</th>
                <th>Donors</th>
                <th>End Date</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              {filteredCampaigns.map((campaign) => (
                <tr key={campaign.id}>
                  <td>
                    <div className="campaign-info">
                      <div className="campaign-title">{campaign.title}</div>
                      <div className="campaign-meta">
                        Created {new Date(campaign.createdAt).toLocaleDateString()}
                      </div>
                    </div>
                  </td>
                  <td>
                    <span className={`status-badge ${getStatusColor(campaign.status)}`}>
                      {campaign.status.charAt(0).toUpperCase() + campaign.status.slice(1)}
                    </span>
                  </td>
                  <td>
                    <div className="progress-info">
                      <div className="progress-amounts">
                        R{campaign.raised.toLocaleString()} of R{campaign.target.toLocaleString()}
                      </div>
                      <div className="progress-bar">
                        <div 
                          className="progress-fill" 
                          style={{width: `${(campaign.raised / campaign.target * 100)}%`}}
                        ></div>
                      </div>
                      <div className="progress-percentage">
                        {Math.round(campaign.raised / campaign.target * 100)}%
                      </div>
                    </div>
                  </td>
                  <td>
                    <div className="donors-info">
                      <i className="fas fa-users"></i>
                      {campaign.donors}
                    </div>
                  </td>
                  <td>
                    <div className="end-date">
                      {new Date(campaign.endDate).toLocaleDateString()}
                    </div>
                  </td>
                  <td>
                    <div className="action-buttons">
                      <button className="btn-icon" title="Edit">
                        <i className="fas fa-edit"></i>
                      </button>
                      <button className="btn-icon" title="Analytics">
                        <i className="fas fa-chart-line"></i>
                      </button>
                      <button className="btn-icon" title="Share">
                        <i className="fas fa-share"></i>
                      </button>
                      <button className="btn-icon text-red-600" title="Delete">
                        <i className="fas fa-trash"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>

      {/* Create Campaign Modal */}
      {showCreateModal && (
        <CampaignModal 
          onClose={() => setShowCreateModal(false)}
          onSave={(newCampaign) => {
            setCampaigns([...campaigns, { ...newCampaign, id: Date.now() }]);
            setShowCreateModal(false);
          }}
        />
      )}
    </div>
  );
};

// Campaign Creation/Edit Modal
const CampaignModal = ({ campaign = null, onClose, onSave }) => {
  const [formData, setFormData] = useState({
    title: campaign?.title || '',
    description: campaign?.description || '',
    target: campaign?.target || '',
    goalType: campaign?.goalType || 'currency',
    category: campaign?.category || 'education',
    endDate: campaign?.endDate || '',
    featured: campaign?.featured || false
  });

  const handleSubmit = (e) => {
    e.preventDefault();
    onSave({
      ...formData,
      raised: campaign?.raised || 0,
      donors: campaign?.donors || 0,
      status: campaign?.status || 'draft',
      createdAt: campaign?.createdAt || new Date().toISOString().split('T')[0]
    });
  };

  return (
    <div className="modal-overlay">
      <div className="modal-content large">
        <div className="modal-header">
          <h3>{campaign ? 'Edit Campaign' : 'Create New Campaign'}</h3>
          <button onClick={onClose} className="modal-close">
            <i className="fas fa-times"></i>
          </button>
        </div>

        <form onSubmit={handleSubmit} className="campaign-form">
          <div className="form-grid">
            <div className="form-group">
              <label className="form-label">Campaign Title *</label>
              <input
                type="text"
                value={formData.title}
                onChange={(e) => setFormData({...formData, title: e.target.value})}
                className="form-input"
                required
                placeholder="Enter campaign title"
              />
            </div>

            <div className="form-group">
              <label className="form-label">Category *</label>
              <select
                value={formData.category}
                onChange={(e) => setFormData({...formData, category: e.target.value})}
                className="form-input"
                required
              >
                <option value="education">Education</option>
                <option value="health">Health</option>
                <option value="environment">Environment</option>
                <option value="community">Community</option>
                <option value="emergency">Emergency Relief</option>
              </select>
            </div>

            <div className="form-group span-2">
              <label className="form-label">Description *</label>
              <textarea
                value={formData.description}
                onChange={(e) => setFormData({...formData, description: e.target.value})}
                className="form-input"
                rows="4"
                required
                placeholder="Describe your campaign and its impact..."
              />
            </div>

            <div className="form-group">
              <label className="form-label">Target Amount (R) *</label>
              <input
                type="number"
                value={formData.target}
                onChange={(e) => setFormData({...formData, target: e.target.value})}
                className="form-input"
                required
                min="1"
                placeholder="50000"
              />
            </div>

            <div className="form-group">
              <label className="form-label">End Date *</label>
              <input
                type="date"
                value={formData.endDate}
                onChange={(e) => setFormData({...formData, endDate: e.target.value})}
                className="form-input"
                required
              />
            </div>

            <div className="form-group span-2">
              <label className="checkbox-label">
                <input
                  type="checkbox"
                  checked={formData.featured}
                  onChange={(e) => setFormData({...formData, featured: e.target.checked})}
                />
                <span>Feature this campaign on homepage</span>
              </label>
            </div>
          </div>

          <div className="modal-actions">
            <button type="button" onClick={onClose} className="btn btn-outline">
              Cancel
            </button>
            <button type="submit" className="btn btn-primary">
              <i className="fas fa-save"></i>
              {campaign ? 'Update Campaign' : 'Create Campaign'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};