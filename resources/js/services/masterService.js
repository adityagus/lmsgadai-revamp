import { apiInstance } from '../utils/axios';

export const getWilayah = async () => {
  try {
    const response = await apiInstance.get('/master/wilayah');
    console.log('response wilayah', response);
    return response.data;
  } catch (error) {
    console.error('Error fetching wilayah:', error);
    throw error;
  }
}

export const getTypesByIdMenu = async (id_menu) => {
  try {
    const response = await apiInstance.get(`/master/types/${id_menu}`);
    console.log('response', response);
    return response.data;
  } catch (error) {
    console.error('Error fetching types by menu ID:', error);
    throw error;
  }
}

export const getJabatan = async () => {
  try {
    const response = await apiInstance.get('/master/jabatan');
    return response.data;
  } catch (error) {
    console.error('Error fetching jabatan:', error);
    throw error;
  }
}

export const searchContent = async (query) => {
  try {
    const response = await apiInstance.get('/content/search', {
      params: { q: query }
    });
    return response.data;
  } catch (error) {
    console.error('Error searching content:', error);
    throw error;
  }
}

export const getCabang = async () => {
  try {
    const response = await apiInstance.get('/master/cabang');
    console.log('response cabang', response);
    return response.data;
  } catch (error) {
    console.error('Internal Server Error:', error);
    throw error;
  }
}

export const userOverview = async () => {
  try {
    const response = await apiInstance.get('/api/user-overview');
    console.log('response user overview', response);
    return response;
  } catch (error) {
    console.error('Error fetching user overview:', error);
    throw error;
  }
}

export const adminOverview = async () => {
  try {
    const response = await apiInstance.get('/api/admin-overview');
    console.log('response admin overview', response);
    return response;
  } catch (error) {
    console.error('Error fetching admin overview:', error);
    throw error;
  }
}