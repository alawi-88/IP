import axios, { AxiosError, AxiosResponse } from "axios";
import Cookies from "js-cookie";
import Router from "next/router";
import { useUserStore } from "./store/user";
import { logoutAndRedirect } from "./lib/utils/logout";

export const UploadEndpoint =
  process.env.NEXT_PUBLIC_API_ENDPOINT + "/upload-to-gcs";

export interface APIError extends AxiosError {
  response: AxiosResponse<{
    message: string;
    errors?: Record<string, Array<string>>;
  }>;
}

const axiosInstance = axios.create({
  baseURL: process.env.NEXT_PUBLIC_API_ENDPOINT,
  headers: {
    Accept: "application/json",
  },
});

axiosInstance.interceptors.request.use(
  function (config) {
    // Get the authorization token from cookies
    const access_token = Cookies.get("accessToken");

    // Set the current locale
    config.headers["Accept-Language"] = Cookies.get("NEXT_LOCALE") || "en";

    // If the authorization token exists, add it to the request headers
    if (access_token) {
      config.headers["Authorization"] = `Bearer ${access_token}`;
    }
    return config;
  },
  function (error) {
    return Promise.reject(error);
  }
);

// Response Interceptor to handle 401 Unauthenticated
axiosInstance.interceptors.response.use(
  (response) => response,
  (error: APIError) => {
    if (
      error.response?.status === 401 &&
      error.response?.data?.message?.toLowerCase() === "unauthenticated."
    ) {
      logoutAndRedirect();
    }

    return Promise.reject(error);
  }
);

export default axiosInstance;
