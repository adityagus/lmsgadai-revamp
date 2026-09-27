import { apiInstance } from '../utils/axios';

/**
 * Record a document view for the current user.
 * 
 * @param {number|string} documentId 
 * @returns {Promise<any>}
 */
export const recordDocumentView = async (documentId) => {
  try {
    const response = await apiInstance.post('/document-views', {
      document_id: documentId
    });
    return response.data;
  } catch (error) {
    console.error('Error recording document view:', error);
    throw error;
  }
};

/**
 * Check if the current user has viewed a document.
 * 
 * @param {number|string} documentId 
 * @returns {Promise<any>}
 */
export const checkDocumentViewed = async (documentId) => {
  try {
    const response = await apiInstance.get(`/document-views/document/${documentId}/check`);
    return response.data;
  } catch (error) {
    console.error('Error checking document view status:', error);
    throw error;
  }
};

/**
 * Get users who have not viewed a specific document.
 * 
 * @param {number|string} documentId 
 * @returns {Promise<any>}
 */
export const getUnviewedUsers = async (documentId) => {
  try {
    const response = await apiInstance.get(`/document-views/document/${documentId}/unviewed`);
    return response.data;
  } catch (error) {
    console.error('Error fetching unviewed users:', error);
    throw error;
  }
};

/**
 * Get users who have viewed a specific document.
 * 
 * @param {number|string} documentId 
 * @returns {Promise<any>}
 */
export const getViewedUsers = async (documentId) => {
  try {
    const response = await apiInstance.get(`/document-views/document/${documentId}/viewed`);
    return response.data;
  } catch (error) {
    console.error('Error fetching viewed users:', error);
    throw error;
  }
};

/**
 * Get overall document view statistics.
 * 
 * @returns {Promise<any>}
 */
export const getDocumentViewStats = async () => {
  try {
    const response = await apiInstance.get('/document-views/stats');
    return response.data;
  } catch (error) {
    console.error('Error fetching document view stats:', error);
    throw error;
  }
};
