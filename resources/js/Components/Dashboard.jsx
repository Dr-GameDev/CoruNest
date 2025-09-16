// Dashboard Layout Component
const DashboardLayout = ({ children }) => {
    return (
        <div className="dashboard-layout">
            <aside className="sidebar">
                <div className="sidebar-logo">
                    <i className="fas fa-heart"></i> CoruNest
                </div>
                <nav>
                    <ul className="sidebar-nav">
                        <li><a href="/admin" className="active"><i className="fas fa-tachometer-alt"></i> Dashboard</a></li>
                        <li><a href="/admin/campaigns"><i className="fas fa-bullhorn"></i> Campaigns</a></li>
                        <li><a href="/admin/donations"><i className="fas fa-hand-holding-heart"></i> Donations</a></li>
                        <li><a href="/admin/events"><i className="fas fa-calendar-alt"></i> Events</a></li>
                        <li><a href="/admin/volunteers"><i className="fas fa-users"></i> Volunteers</a></li>
                        <li><a href="/admin/analytics"><i className="fas fa-chart-line"></i> Analytics</a></li>
                        <li><a href="/admin/settings"><i className="fas fa-cog"></i> Settings</a></li>
                    </ul>
                </nav>
            </aside>
            <main className="main-content">
                {children}
            </main>
        </div>
    );
};

// Dashboard Overview Component
const DashboardOverview = () => {
    const [stats, setStats] = useState({
        totalRaised: 472000,
        activeCampaigns: 12,
        totalVolunteers: 2840,
        upcomingEvents: 8
    });

    return (
        <div>
            <div className="dashboard-header">
                <h1 className="page-title">Dashboard Overview</h1>
                <button className="btn btn-primary">
                    <i className="fas fa-plus"></i> Create Campaign
                </button>
            </div>

            <div className="kpi-grid">
                <div className="kpi-card">
                    <div className="kpi-label">Total Raised</div>
                    <div className="kpi-value">R{stats.totalRaised.toLocaleString()}</div>
                    <div className="kpi-trend positive">
                        <i className="fas fa-arrow-up"></i> +12% from last month
                    </div>
                </div>

                <div className="kpi-card">
                    <div className="kpi-label">Active Campaigns</div>
                    <div className="kpi-value">{stats.activeCampaigns}</div>
                    <div className="kpi-trend positive">
                        <i className="fas fa-arrow-up"></i> +3 new this week
                    </div>
                </div>

                <div className="kpi-card">
                    <div className="kpi-label">Total Volunteers</div>
                    <div className="kpi-value">{stats.totalVolunteers.toLocaleString()}</div>
                    <div className="kpi-trend positive">
                        <i className="fas fa-arrow-up"></i> +156 this month
                    </div>
                </div>

                <div className="kpi-card">
                    <div className="kpi-label">Upcoming Events</div>
                    <div className="kpi-value">{stats.upcomingEvents}</div>
                    <div className="kpi-trend">
                        <i className="fas fa-calendar"></i> Next: Sept 22
                    </div>
                </div>
            </div>

            <div className="chart-container">
                <h3 style={{ marginBottom: '1rem', fontSize: '1.25rem', fontWeight: '700' }}>
                    Donations Over Time
                </h3>
                <canvas id="donationsChart" width="400" height="200"></canvas>
            </div>
        </div>
    );
};

// Campaign Management Component
const CampaignManagement = () => {
    const [campaigns, setCampaigns] = useState([]);
    const [showCreateModal, setShowCreateModal] = useState(false);

    return (
        <div>
            <div className="dashboard-header">
                <h1 className="page-title">Campaign Management</h1>
                <button className="btn btn-primary" onClick={() => setShowCreateModal(true)}>
                    <i className="fas fa-plus"></i> Create New Campaign
                </button>
            </div>

            <div className="card">
                <table style={{ width: '100%', borderCollapse: 'collapse' }}>
                    <thead>
                        <tr style={{ borderBottom: '1px solid var(--gray-200)' }}>
                            <th style={{ padding: '1rem', textAlign: 'left', fontWeight: '600' }}>Campaign</th>
                            <th style={{ padding: '1rem', textAlign: 'left', fontWeight: '600' }}>Progress</th>
                            <th style={{ padding: '1rem', textAlign: 'left', fontWeight: '600' }}>Status</th>
                            <th style={{ padding: '1rem', textAlign: 'left', fontWeight: '600' }}>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr style={{ borderBottom: '1px solid var(--gray-100)' }}>
                            <td style={{ padding: '1rem' }}>
                                <div>
                                    <div style={{ fontWeight: '600', marginBottom: '0.25rem' }}>Education for All Children</div>
                                    <div style={{ color: 'var(--gray-500)', fontSize: '0.875rem' }}>R125,000 of R200,000</div>
                                </div>
                            </td>
                            <td style={{ padding: '1rem' }}>
                                <div className="progress-bar" style={{ width: '100px' }}>
                                    <div className="progress-fill" style={{ width: '62%' }}></div>
                                </div>
                                <div style={{ fontSize: '0.875rem', marginTop: '0.25rem' }}>62%</div>
                            </td>
                            <td style={{ padding: '1rem' }}>
                                <span style={{
                                    background: 'var(--primary-50)',
                                    color: 'var(--primary-600)',
                                    padding: '0.25rem 0.5rem',
                                    borderRadius: '0.25rem',
                                    fontSize: '0.875rem'
                                }}>Active</span>
                            </td>
                            <td style={{ padding: '1rem' }}>
                                <button className="btn" style={{ padding: '0.5rem', marginRight: '0.5rem' }}>
                                    <i className="fas fa-edit"></i>
                                </button>
                                <button className="btn" style={{ padding: '0.5rem' }}>
                                    <i className="fas fa-chart-line"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    );
};