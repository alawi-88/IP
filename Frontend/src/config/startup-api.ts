import axiosInstance from "@/axios";
import { Startup } from "@/store/startup";

export interface VaPage {
  id: string;
  sectionId: string;
  pageKey: string;
  title: string;
  content: Record<string, any>;
  completedAt?: string;
  lastModified?: string;
}

export interface VaSection {
  id: string;
  key: string;
  title: string;
  description?: string;
  pages: VaPage[];
  completionPercentage: number;
}

export interface StartupData {
  name: string;
  tagline?: string;
  description?: string;
  logo?: File | string;
}

export interface AiGeneration {
  id: string;
  generatedContent: string;
  fieldKey: string;
  status: "pending" | "accepted" | "rejected";
  createdAt: string;
}

// Startup CRUD Operations
export const getStartups = async (): Promise<Startup[]> => {
  const response = await axiosInstance.get("/startups");
  return response.data.data || [];
};

export const createStartup = async (data: StartupData): Promise<Startup> => {
  const formData = new FormData();
  formData.append("name", data.name);
  if (data.tagline) formData.append("tagline", data.tagline);
  if (data.description) formData.append("description", data.description);
  if (data.logo && typeof data.logo !== "string") {
    formData.append("logo", data.logo);
  }

  const response = await axiosInstance.post("/startups", formData, {
    headers: { "Content-Type": "multipart/form-data" },
  });
  return response.data.data;
};

export const getStartup = async (
  startupId: string
): Promise<Startup & { sections: VaSection[] }> => {
  const response = await axiosInstance.get(`/startups/${startupId}`);
  return response.data.data;
};

export const updateStartup = async (
  startupId: string,
  data: Partial<StartupData>
): Promise<Startup> => {
  const formData = new FormData();
  if (data.name) formData.append("name", data.name);
  if (data.tagline) formData.append("tagline", data.tagline);
  if (data.description) formData.append("description", data.description);
  if (data.logo && typeof data.logo !== "string") {
    formData.append("logo", data.logo);
  }

  const response = await axiosInstance.patch(`/startups/${startupId}`, formData, {
    headers: { "Content-Type": "multipart/form-data" },
  });
  return response.data.data;
};

export const deleteStartup = async (startupId: string): Promise<void> => {
  await axiosInstance.delete(`/startups/${startupId}`);
};

// VA Section Operations
export const getVaSection = async (
  startupId: string,
  sectionId: string
): Promise<VaSection> => {
  const response = await axiosInstance.get(
    `/startups/${startupId}/va-sections/${sectionId}`
  );
  return response.data.data;
};

// VA Page Operations
export const getVaPage = async (
  startupId: string,
  sectionId: string,
  pageId: string
): Promise<VaPage> => {
  const response = await axiosInstance.get(
    `/startups/${startupId}/va-sections/${sectionId}/pages/${pageId}`
  );
  return response.data.data;
};

export const updateVaPage = async (
  startupId: string,
  sectionId: string,
  pageId: string,
  content: Record<string, any>
): Promise<VaPage> => {
  const response = await axiosInstance.patch(
    `/startups/${startupId}/va-sections/${sectionId}/pages/${pageId}`,
    { content }
  );
  return response.data.data;
};

export const completeVaPage = async (
  startupId: string,
  sectionId: string,
  pageId: string
): Promise<VaPage> => {
  const response = await axiosInstance.post(
    `/startups/${startupId}/va-sections/${sectionId}/pages/${pageId}/complete`
  );
  return response.data.data;
};

// AI Generation Operations
export const generateAi = async (
  startupId: string,
  sectionId: string,
  pageId: string,
  fieldKey: string,
  prompt: string
): Promise<AiGeneration> => {
  const response = await axiosInstance.post(
    `/startups/${startupId}/va-sections/${sectionId}/pages/${pageId}/ai-generate`,
    { fieldKey, prompt }
  );
  return response.data.data;
};

export const acceptAi = async (
  startupId: string,
  sectionId: string,
  generationId: string
): Promise<AiGeneration> => {
  const response = await axiosInstance.post(
    `/startups/${startupId}/va-sections/${sectionId}/ai-generations/${generationId}/accept`
  );
  return response.data.data;
};

export const modifyAi = async (
  startupId: string,
  sectionId: string,
  generationId: string,
  modifiedContent: string
): Promise<AiGeneration> => {
  const response = await axiosInstance.patch(
    `/startups/${startupId}/va-sections/${sectionId}/ai-generations/${generationId}`,
    { modifiedContent }
  );
  return response.data.data;
};

export const dismissAi = async (
  startupId: string,
  sectionId: string,
  generationId: string
): Promise<void> => {
  await axiosInstance.delete(
    `/startups/${startupId}/va-sections/${sectionId}/ai-generations/${generationId}`
  );
};
