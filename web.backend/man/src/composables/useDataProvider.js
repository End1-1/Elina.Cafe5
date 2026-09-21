import axios from 'axios'

export function useDataProvider() {
  async function getData(route, params = {}) {
    const { data } = await axios.post(route, params, {
      headers: { 'Content-Type': 'application/json',
        'Authorization': `Bearer ${localStorage.getItem('token') || ''}`,
       },
    })
    return data
  }


  return { getData }
}
