import { useState } from 'react';
import { useGraph, useComponentDetail, useDesignChanges, useNotifications } from './hooks';

const CAT_COLORS = {
  magnet: { bg: '#1e3a5f', border: '#4a9eff', text: '#4a9eff' },
  structure: { bg: '#3d1f56', border: '#a855f7', text: '#a855f7' },
  thermal: { bg: '#5c1a1a', border: '#ef4444', text: '#ef4444' },
  plasma: { bg: '#1a4a3a', border: '#10b981', text: '#10b981' },
};

const NODE_POSITIONS = {
  tfc: { x: 300, y: 60 },
  pfc: { x: 520, y: 100 },
  vv: { x: 560, y: 280 },
  blanket: { x: 480, y: 440 },
  divertor: { x: 260, y: 480 },
  heating: { x: 80, y: 380 },
  cryostat: { x: 40, y: 160 },
  fueling: { x: 160, y: 60 },
};

const STRENGTH_COLORS = { high: '#ef4444', medium: '#f59e0b', low: '#6b7280' };

export default function App() {
  const { nodes, edges, loading: graphLoading } = useGraph();
  const { changes, submitChange, submitting, lastAnalysis } = useDesignChanges();
  const { notifications, unreadCount, markRead } = useNotifications();

  const [selectedId, setSelectedId] = useState(null);
  const { component: selectedComponent, impacted } = useComponentDetail(selectedId);

  const [activeTab, setActiveTab] = useState('graph');
  const [showModal, setShowModal] = useState(false);
  const [showAnalysis, setShowAnalysis] = useState(false);

  const selectedNode = nodes.find(n => n.id === selectedId);

  const handleNodeClick = (node) => {
    setSelectedId(node.id === selectedId ? null : node.id);
    setShowAnalysis(false);
  };

  const handleSubmitChange = async (formData) => {
    const result = await submitChange(formData);
    if (result) {
      setShowModal(false);
      setShowAnalysis(true);
    }
  };

  if (graphLoading) {
    return (
      <div style={{
        minHeight: '100vh', background: '#0a0f1a', color: '#e2e8f0',
        display: 'flex', alignItems: 'center', justifyContent: 'center',
        fontFamily: 'system-ui, sans-serif'
      }}>
        <div style={{ textAlign: 'center' }}>
          <div style={{ fontSize: 32, marginBottom: 16 }}>⚛</div>
          <div>データを読み込み中...</div>
        </div>
      </div>
    );
  }

  return (
    <div style={{
      minHeight: '100vh', background: '#0a0f1a', color: '#e2e8f0',
      fontFamily: 'system-ui, sans-serif'
    }}>
      {/* Header */}
      <div style={{
        background: '#111827', borderBottom: '1px solid #1e293b',
        padding: '12px 24px', display: 'flex', alignItems: 'center', justifyContent: 'space-between'
      }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
          <span style={{ fontSize: 24 }}>⚛</span>
          <span style={{ fontSize: 18, fontWeight: 700, color: '#4a9eff' }}>
            Fusion Design Tracker
          </span>
          <span style={{
            fontSize: 11, background: '#1e3a5f', color: '#4a9eff',
            padding: '2px 8px', borderRadius: 4
          }}>
            API Connected
          </span>
        </div>
        <div style={{ display: 'flex', gap: 8 }}>
          {['graph', 'changes', 'notifications'].map(tab => (
            <button
              key={tab}
              onClick={() => setActiveTab(tab)}
              style={{
                padding: '6px 16px', borderRadius: 6, border: 'none', cursor: 'pointer',
                fontSize: 13, fontWeight: 500,
                background: activeTab === tab ? '#4a9eff' : '#1e293b',
                color: activeTab === tab ? '#fff' : '#94a3b8',
              }}
            >
              {tab === 'graph' ? '依存グラフ' : tab === 'changes' ? '変更履歴' : `通知${unreadCount > 0 ? ` (${unreadCount})` : ''}`}
            </button>
          ))}
        </div>
      </div>

      <div style={{ display: 'flex', height: 'calc(100vh - 56px)' }}>
        {/* Main Content */}
        <div style={{ flex: 1, position: 'relative', overflow: 'hidden' }}>
          {activeTab === 'graph' && (
            <GraphView
              nodes={nodes}
              edges={edges}
              selectedId={selectedId}
              impacted={impacted}
              onNodeClick={handleNodeClick}
            />
          )}
          {activeTab === 'changes' && (
            <ChangesList changes={changes} />
          )}
          {activeTab === 'notifications' && (
            <NotificationsList notifications={notifications} onMarkRead={markRead} />
          )}

          {/* Register Button */}
          <button
            onClick={() => setShowModal(true)}
            style={{
              position: 'absolute', bottom: 24, right: 24,
              background: '#4a9eff', color: '#fff', border: 'none',
              padding: '12px 24px', borderRadius: 8, cursor: 'pointer',
              fontSize: 14, fontWeight: 600, boxShadow: '0 4px 12px rgba(74,158,255,0.3)',
            }}
          >
            + 設計変更を登録
          </button>
        </div>

        {/* Side Panel */}
        <div style={{
          width: 360, background: '#111827', borderLeft: '1px solid #1e293b',
          padding: 20, overflowY: 'auto'
        }}>
          {showAnalysis && lastAnalysis ? (
            <AnalysisPanel analysis={lastAnalysis} onClose={() => setShowAnalysis(false)} />
          ) : selectedComponent ? (
            <ComponentDetail component={selectedComponent} impacted={impacted} />
          ) : (
            <div style={{ color: '#64748b', textAlign: 'center', marginTop: 80 }}>
              <div style={{ fontSize: 48, marginBottom: 16 }}>⚛</div>
              <div>コンポーネントをクリックして</div>
              <div>詳細を表示</div>
            </div>
          )}
        </div>
      </div>

      {/* Modal */}
      {showModal && (
        <ChangeModal
          nodes={nodes}
          selectedId={selectedId}
          submitting={submitting}
          onSubmit={handleSubmitChange}
          onClose={() => setShowModal(false)}
        />
      )}
    </div>
  );
}

function GraphView({ nodes, edges, selectedId, impacted, onNodeClick }) {
  const impactedIds = impacted.map(i => i.id);

  return (
    <svg width="100%" height="100%" viewBox="0 0 700 560" style={{ background: '#0a0f1a' }}>
      {/* Edges */}
      {edges.map(edge => {
        const fromNode = nodes.find(n => n.id === edge.from_component_id);
        const toNode = nodes.find(n => n.id === edge.to_component_id);
        if (!fromNode || !toNode) return null;
        const from = NODE_POSITIONS[fromNode.code];
        const to = NODE_POSITIONS[toNode.code];
        if (!from || !to) return null;

        const isHighlighted = selectedId &&
          (edge.from_component_id === selectedId || impactedIds.includes(edge.to_component_id));

        return (
          <line
            key={edge.id}
            x1={from.x + 60} y1={from.y + 25}
            x2={to.x + 60} y2={to.y + 25}
            stroke={isHighlighted ? STRENGTH_COLORS[edge.strength] : '#1e293b'}
            strokeWidth={isHighlighted ? 2.5 : 1}
            opacity={selectedId ? (isHighlighted ? 1 : 0.15) : 0.4}
          />
        );
      })}

      {/* Nodes */}
      {nodes.map(node => {
        const pos = NODE_POSITIONS[node.code];
        if (!pos) return null;
        const colors = CAT_COLORS[node.category] || CAT_COLORS.structure;
        const isSelected = node.id === selectedId;
        const isImpacted = impactedIds.includes(node.id);
        const dimmed = selectedId && !isSelected && !isImpacted;

        return (
          <g
            key={node.id}
            onClick={() => onNodeClick(node)}
            style={{ cursor: 'pointer' }}
            opacity={dimmed ? 0.2 : 1}
          >
            <rect
              x={pos.x} y={pos.y} width={120} height={50} rx={8}
              fill={colors.bg}
              stroke={isSelected ? '#fff' : isImpacted ? '#f59e0b' : colors.border}
              strokeWidth={isSelected ? 2.5 : isImpacted ? 2 : 1}
            />
            <text
              x={pos.x + 60} y={pos.y + 20}
              textAnchor="middle" fill={colors.text}
              fontSize={12} fontWeight={600}
            >
              {node.owner_avatar} {node.name}
            </text>
            <text
              x={pos.x + 60} y={pos.y + 38}
              textAnchor="middle" fill="#94a3b8" fontSize={9}
            >
              {node.name_en}
            </text>
          </g>
        );
      })}
    </svg>
  );
}

function ComponentDetail({ component, impacted }) {
  const colors = CAT_COLORS[component.category] || CAT_COLORS.structure;

  return (
    <div>
      <div style={{
        background: colors.bg, border: `1px solid ${colors.border}`,
        borderRadius: 8, padding: 16, marginBottom: 16
      }}>
        <div style={{ fontSize: 20, fontWeight: 700, color: colors.text }}>
          {component.owner_avatar} {component.name}
        </div>
        <div style={{ fontSize: 12, color: '#94a3b8', marginTop: 4 }}>
          {component.name_en}
        </div>
        <div style={{ fontSize: 13, color: '#cbd5e1', marginTop: 8 }}>
          {component.description}
        </div>
        <div style={{ fontSize: 12, color: '#64748b', marginTop: 8 }}>
          担当: {component.owner_team}
        </div>
      </div>

      {/* Specs */}
      {component.specs && (
        <div style={{ marginBottom: 16 }}>
          <div style={{ fontSize: 13, fontWeight: 600, color: '#94a3b8', marginBottom: 8 }}>
            スペック
          </div>
          {Object.entries(component.specs).map(([key, value]) => (
            <div key={key} style={{
              display: 'flex', justifyContent: 'space-between',
              fontSize: 12, padding: '4px 0', borderBottom: '1px solid #1e293b'
            }}>
              <span style={{ color: '#64748b' }}>{key}</span>
              <span style={{ color: '#e2e8f0' }}>{value}</span>
            </div>
          ))}
        </div>
      )}

      {/* Impacted */}
      {impacted.length > 0 && (
        <div>
          <div style={{ fontSize: 13, fontWeight: 600, color: '#94a3b8', marginBottom: 8 }}>
            影響先 ({impacted.length}件)
          </div>
          {impacted.map(imp => (
            <div key={imp.id} style={{
              background: '#0f172a', borderRadius: 6, padding: 10, marginBottom: 6,
              borderLeft: `3px solid ${STRENGTH_COLORS[imp.strength]}`
            }}>
              <div style={{ fontSize: 13, fontWeight: 600, color: '#e2e8f0' }}>
                {imp.name}
              </div>
              <div style={{ fontSize: 11, color: '#94a3b8', marginTop: 2 }}>
                {imp.relation}
              </div>
              <div style={{ fontSize: 10, color: STRENGTH_COLORS[imp.strength], marginTop: 2 }}>
                影響度: {imp.strength} / {imp.depth === 1 ? '直接' : '間接'}
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}

function ChangeModal({ nodes, selectedId, submitting, onSubmit, onClose }) {
  const [form, setForm] = useState({
    componentId: selectedId || (nodes[0]?.id || ''),
    title: '',
    description: '',
    author: '',
  });

  const handleSubmit = (e) => {
    e.preventDefault();
    onSubmit(form);
  };

  const inputStyle = {
    width: '100%', padding: '8px 12px', borderRadius: 6,
    border: '1px solid #334155', background: '#0f172a',
    color: '#e2e8f0', fontSize: 13, boxSizing: 'border-box',
  };

  return (
    <div style={{
      position: 'fixed', inset: 0, background: 'rgba(0,0,0,0.7)',
      display: 'flex', alignItems: 'center', justifyContent: 'center', zIndex: 100,
    }}>
      <form onSubmit={handleSubmit} style={{
        background: '#1e293b', borderRadius: 12, padding: 28, width: 460,
        border: '1px solid #334155',
      }}>
        <div style={{ fontSize: 18, fontWeight: 700, color: '#4a9eff', marginBottom: 20 }}>
          設計変更を登録
        </div>

        <div style={{ marginBottom: 14 }}>
          <label style={{ fontSize: 12, color: '#94a3b8', display: 'block', marginBottom: 4 }}>
            対象コンポーネント
          </label>
          <select
            value={form.componentId}
            onChange={e => setForm({ ...form, componentId: Number(e.target.value) })}
            style={inputStyle}
          >
            {nodes.map(n => (
              <option key={n.id} value={n.id}>{n.owner_avatar} {n.name}</option>
            ))}
          </select>
        </div>

        <div style={{ marginBottom: 14 }}>
          <label style={{ fontSize: 12, color: '#94a3b8', display: 'block', marginBottom: 4 }}>
            変更タイトル
          </label>
          <input
            required
            value={form.title}
            onChange={e => setForm({ ...form, title: e.target.value })}
            placeholder="例: TFコイル断面形状をD型→修正D型に変更"
            style={inputStyle}
          />
        </div>

        <div style={{ marginBottom: 14 }}>
          <label style={{ fontSize: 12, color: '#94a3b8', display: 'block', marginBottom: 4 }}>
            変更内容
          </label>
          <textarea
            required
            rows={3}
            value={form.description}
            onChange={e => setForm({ ...form, description: e.target.value })}
            placeholder="変更の詳細を記述してください"
            style={{ ...inputStyle, resize: 'vertical' }}
          />
        </div>

        <div style={{ marginBottom: 20 }}>
          <label style={{ fontSize: 12, color: '#94a3b8', display: 'block', marginBottom: 4 }}>
            起票者
          </label>
          <input
            required
            value={form.author}
            onChange={e => setForm({ ...form, author: e.target.value })}
            placeholder="名前"
            style={inputStyle}
          />
        </div>

        <div style={{ display: 'flex', gap: 8, justifyContent: 'flex-end' }}>
          <button
            type="button" onClick={onClose}
            style={{
              padding: '8px 20px', borderRadius: 6, border: '1px solid #334155',
              background: 'transparent', color: '#94a3b8', cursor: 'pointer', fontSize: 13,
            }}
          >
            キャンセル
          </button>
          <button
            type="submit" disabled={submitting}
            style={{
              padding: '8px 20px', borderRadius: 6, border: 'none',
              background: submitting ? '#334155' : '#4a9eff',
              color: '#fff', cursor: submitting ? 'wait' : 'pointer', fontSize: 13, fontWeight: 600,
            }}
          >
            {submitting ? 'AI分析中...' : '登録してAI分析'}
          </button>
        </div>
      </form>
    </div>
  );
}

function AnalysisPanel({ analysis, onClose }) {
  return (
    <div>
      <div style={{
        display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 16
      }}>
        <div style={{ fontSize: 16, fontWeight: 700, color: '#10b981' }}>
          AI 影響分析結果
        </div>
        <button
          onClick={onClose}
          style={{
            background: '#1e293b', border: '1px solid #334155', color: '#94a3b8',
            borderRadius: 4, padding: '4px 8px', cursor: 'pointer', fontSize: 12,
          }}
        >
          ✕
        </button>
      </div>

      {/* Change Info */}
      {analysis.change && (
        <div style={{
          background: '#0f172a', borderRadius: 8, padding: 12, marginBottom: 12,
          border: '1px solid #1e293b'
        }}>
          <div style={{ fontSize: 14, fontWeight: 600, color: '#e2e8f0' }}>
            {analysis.change.change_code}: {analysis.change.title}
          </div>
          <div style={{ fontSize: 12, color: '#64748b', marginTop: 4 }}>
            {analysis.change.component?.name} / {analysis.change.author}
          </div>
        </div>
      )}

      {/* AI Summary */}
      <div style={{
        background: '#0f172a', borderRadius: 8, padding: 12, marginBottom: 12,
        border: '1px solid #1e3a5f', fontSize: 13, color: '#cbd5e1',
        whiteSpace: 'pre-wrap', lineHeight: 1.6, maxHeight: 300, overflowY: 'auto',
      }}>
        {analysis.ai_summary}
      </div>

      {/* Documents */}
      {analysis.documents?.length > 0 && (
        <div style={{ marginBottom: 12 }}>
          <div style={{ fontSize: 13, fontWeight: 600, color: '#94a3b8', marginBottom: 6 }}>
            要更新ドキュメント ({analysis.documents.length}件)
          </div>
          {analysis.documents.map(doc => (
            <div key={doc.id} style={{
              fontSize: 12, color: '#cbd5e1', padding: '4px 0',
              borderBottom: '1px solid #1e293b'
            }}>
              📄 {doc.name} ({doc.doc_type})
            </div>
          ))}
        </div>
      )}

      {/* Notifications */}
      {analysis.notifications?.length > 0 && (
        <div>
          <div style={{ fontSize: 13, fontWeight: 600, color: '#94a3b8', marginBottom: 6 }}>
            送信された通知 ({analysis.notifications.length}件)
          </div>
          {analysis.notifications.map(notif => (
            <div key={notif.id} style={{
              background: '#0f172a', borderRadius: 6, padding: 8, marginBottom: 4,
              borderLeft: `3px solid ${STRENGTH_COLORS[notif.strength]}`,
              fontSize: 12, color: '#cbd5e1',
            }}>
              {notif.team}: {notif.message}
            </div>
          ))}
        </div>
      )}
    </div>
  );
}

function ChangesList({ changes }) {
  if (!changes.length) {
    return (
      <div style={{ padding: 40, textAlign: 'center', color: '#64748b' }}>
        設計変更はまだ登録されていません
      </div>
    );
  }

  return (
    <div style={{ padding: 24, overflowY: 'auto', height: '100%' }}>
      <div style={{ fontSize: 18, fontWeight: 700, color: '#e2e8f0', marginBottom: 16 }}>
        変更履歴 ({changes.length}件)
      </div>
      {changes.map(change => (
        <div key={change.id} style={{
          background: '#111827', borderRadius: 8, padding: 16, marginBottom: 8,
          border: '1px solid #1e293b',
        }}>
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
            <div>
              <span style={{ fontSize: 12, color: '#4a9eff', fontWeight: 600 }}>
                {change.change_code}
              </span>
              <span style={{ fontSize: 14, color: '#e2e8f0', marginLeft: 8, fontWeight: 600 }}>
                {change.title}
              </span>
            </div>
            <span style={{
              fontSize: 11, padding: '2px 8px', borderRadius: 4,
              background: change.status === 'approved' ? '#064e3b' : '#1e293b',
              color: change.status === 'approved' ? '#10b981' : '#94a3b8',
            }}>
              {change.status}
            </span>
          </div>
          <div style={{ fontSize: 12, color: '#64748b', marginTop: 4 }}>
            {change.component?.name} / {change.author} / {new Date(change.created_at).toLocaleDateString('ja-JP')}
          </div>
        </div>
      ))}
    </div>
  );
}

function NotificationsList({ notifications, onMarkRead }) {
  if (!notifications.length) {
    return (
      <div style={{ padding: 40, textAlign: 'center', color: '#64748b' }}>
        通知はありません
      </div>
    );
  }

  return (
    <div style={{ padding: 24, overflowY: 'auto', height: '100%' }}>
      <div style={{ fontSize: 18, fontWeight: 700, color: '#e2e8f0', marginBottom: 16 }}>
        通知一覧
      </div>
      {notifications.map(notif => (
        <div key={notif.id} style={{
          background: notif.status === 'unread' ? '#1e293b' : '#111827',
          borderRadius: 8, padding: 16, marginBottom: 8,
          border: '1px solid #1e293b',
          borderLeft: `3px solid ${STRENGTH_COLORS[notif.strength]}`,
        }}>
          <div style={{ fontSize: 13, color: '#e2e8f0' }}>{notif.message}</div>
          <div style={{
            display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginTop: 8
          }}>
            <span style={{ fontSize: 11, color: '#64748b' }}>
              {notif.team} / {new Date(notif.created_at).toLocaleDateString('ja-JP')}
            </span>
            {notif.status === 'unread' && (
              <button
                onClick={() => onMarkRead(notif.id)}
                style={{
                  fontSize: 11, padding: '2px 8px', borderRadius: 4,
                  border: '1px solid #334155', background: 'transparent',
                  color: '#94a3b8', cursor: 'pointer',
                }}
              >
                既読にする
              </button>
            )}
          </div>
        </div>
      ))}
    </div>
  );
}