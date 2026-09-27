import { apiInstance } from "../utils/axios";

export const getContentsByCourseId = async (courseId) => {
  try {
    const response = await apiInstance.get(`/course/contents/${courseId}`);
    return response.data;
    
  } catch (error) {
    console.error('Error fetching contents by course ID:', error);
    throw error;
  }
};

export const getContentById = async (id) => {
  try {
    const response = await apiInstance.get(`/contents/${id}`);
    return response.data;
  } catch (error) {
    console.error('Error fetching content by ID:', error);
    throw error;
  }
};

export const createContent = async (data) => {
  console.log('Creating content with data:', data);
  const response = await apiInstance.post('/contents', data);
  return response.data;
};

function logFormData(formData) {
  if (!(formData instanceof FormData)) return;
  for (let pair of formData.entries()) {
    console.log(pair[0]+ ':', pair[1]);
  }
}

export const updateContent = async (data, id) => {
  if (data instanceof FormData) {
    logFormData(data);
    // Kirim dengan POST + _method=PUT agar Laravel bisa menerima FormData
    const response = await apiInstance.post(`/contents/${id}`, data);
    return response.data;
  } else {
    console.log('Updating content with data:', data);
    const response = await apiInstance.put(`/contents/${id}`, data);
    return response.data;
  }
};

export const deleteContent = async (id) => {
  const response = await apiInstance.delete(`/contents/${id}`);
  return response.data;
};
