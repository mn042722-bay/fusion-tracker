import { useState, useEffect, useCallback } from 'react';
import api from './api';

export function useGraph() {
  const [nodes, setNodes] = useState([]);
  const [edges, setEdges] = useState([]);
  const [loading, setLoading] = useState(true);

  const fetchGraph = useCallback(async () => {
    try {
      setLoading(true);
      const data = await api.components.graph();
      setNodes(data.nodes);
      setEdges(data.edges);
    } catch (err) {
      console.error('Graph fetch error:', err);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => { fetchGraph(); }, [fetchGraph]);

  return { nodes, edges, loading, refresh: fetchGraph };
}

export function useComponentDetail(componentId) {
  const [component, setComponent] = useState(null);
  const [impacted, setImpacted] = useState([]);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    if (!componentId) {
      setComponent(null);
      setImpacted([]);
      return;
    }

    let cancelled = false;
    setLoading(true);

    api.components.show(componentId).then((data) => {
      if (!cancelled) {
        setComponent(data.component);
        setImpacted(data.impacted);
        setLoading(false);
      }
    }).catch(() => {
      if (!cancelled) setLoading(false);
    });

    return () => { cancelled = true; };
  }, [componentId]);

  return { component, impacted, loading };
}

export function useDesignChanges() {
  const [changes, setChanges] = useState([]);
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [lastAnalysis, setLastAnalysis] = useState(null);

  const fetchChanges = useCallback(async () => {
    try {
      setLoading(true);
      const data = await api.designChanges.list();
      setChanges(data.data || data);
    } catch (err) {
      console.error('Failed to fetch changes:', err);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => { fetchChanges(); }, [fetchChanges]);

  const submitChange = useCallback(async ({ componentId, title, description, author }) => {
    setSubmitting(true);
    try {
      const result = await api.designChanges.create({
        component_id: componentId,
        title,
        description,
        author,
      });
      setLastAnalysis(result);
      await fetchChanges();
      return result;
    } finally {
      setSubmitting(false);
    }
  }, [fetchChanges]);

  return {
    changes,
    loading,
    submitting,
    lastAnalysis,
    submitChange,
    refresh: fetchChanges,
  };
}

export function useNotifications() {
  const [notifications, setNotifications] = useState([]);
  const [unreadCount, setUnreadCount] = useState(0);
  const [loading, setLoading] = useState(true);

  const fetchNotifications = useCallback(async () => {
    try {
      setLoading(true);
      const [notifData, countData] = await Promise.all([
        api.notifications.list(),
        api.notifications.unreadCount(),
      ]);
      setNotifications(notifData.data || notifData);
      setUnreadCount(countData.unread_count);
    } catch (err) {
      console.error('Failed to fetch notifications:', err);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => { fetchNotifications(); }, [fetchNotifications]);

  const markRead = useCallback(async (id) => {
    await api.notifications.markRead(id);
    await fetchNotifications();
  }, [fetchNotifications]);

  return {
    notifications,
    unreadCount,
    loading,
    markRead,
    refresh: fetchNotifications,
  };
}