import { apiInstance } from './../utils/axios.js';

export const getCourses = async (page = 1) => {
  try {
    const response = await apiInstance.get(`/api/courses?page=${page}`);
    return response.data;
  } catch (error) {
    console.error("Error fetching courses:", error);
    throw error;
  }
};

export const searchContent = async (q) => {
  try {
    const response = await apiInstance.get(`/api/search?q=${q}`);
    return response;
  } catch (error) {
    console.error("Error fetching courses:", error);
    throw error;
  }
}

export const deleteCourse = async (id) => {
  try {
    const response = await apiInstance.delete(`/api/courses/${id}`);
    return response.data;
  } catch (error) {
    if (error.response && error.response.data) {
      console.error("Error deleting course:", error.response.data);
      throw error.response.data;
    } else {
      console.error("Error deleting course:", error);
      throw error;
    }
  }
}

export const createCourse = async (courseData) => {
  console.dir(courseData);
  try {
    const response = await apiInstance.post('/api/courses',courseData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    });
    return response.data;
  } catch (error) {
    console.error("Error creating course:", error);
    throw error;
  }
}

export const updateCourse = async (formData, id) => {
  console.log("Update course data:", formData);
  try {
    let response;
    if (formData instanceof FormData) {
      formData.append('_method', 'PUT'); // Laravel method override
      response = await apiInstance.post(`/api/courses/${id}`, formData, {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      });
    } else {
      // Jika object biasa (tidak ada file upload), gunakan PUT dengan JSON
      response = await apiInstance.put(`/api/courses/${id}`, formData, {
        headers: {
          'Content-Type': 'application/json'
        }
      });
    }
    return response.data;
  } catch (error) {
    console.error("Error updating course:", error.response?.data || error);
    throw error;
  }
}

export const getCourseById = async (id, page = 1) => {
  try {
    const response = await apiInstance.get(`/api/preview-course/${id}`);
    return response.data;
  } catch (error) {
    console.error("Error fetching course by ID:", error);
    throw error;
  }
}

export const getCourseByIdPagination = async (id, page = 1) => {
  try {
    const response = await apiInstance.get(`/api/courses/${id}?page=${page}`);
    return response.data;
  } catch (error) {
    console.error("Error fetching course by ID:", error);
    throw error;
  }
}

export const getCategories = async () => {
  try {
    const response = await apiInstance.get('/api/categories');
    return response.data;
  } catch (error) {
    console.error("Error fetching categories:", error);
    throw error;
  }
}