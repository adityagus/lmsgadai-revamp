import { apiInstance, apiInstanceAuth } from '../utils/axios';

export const login = async (data) => {
  const { data: session } = await apiInstanceAuth.get('/csrf-token');
  const response = await apiInstanceAuth.post('/login', data, {
    headers: { 'X-CSRF-TOKEN': session.csrf_token },
  });
  return response.data;
}

export const signOut = async () => {
  try {
    const response = await apiInstanceAuth.post('/logout');
    return response.data;
  } catch (error) {
    console.error('Error during logout:', error);
    throw error;
  }
}



export const getSession = async () => {
  try {
    const response = await apiInstanceAuth.get('/session');
    return response.data;
  } catch (error) {
    console.error('Error fetching session data:', error);
    throw error;
  }
}
