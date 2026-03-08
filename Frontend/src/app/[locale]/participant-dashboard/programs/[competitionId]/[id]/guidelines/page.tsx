"use client";

import axiosInstance from "@/axios";
import Empty from "@/components/Empty";
import { useQuery } from "@tanstack/react-query";
import { Button, Card, Col, Flex, Modal, Spin } from "antd";
import { useTranslations } from "next-intl";
import { useParams } from "next/navigation";
import { MdPictureAsPdf, MdDownload } from "react-icons/md";
import { PiFiles } from "react-icons/pi";
import { FaRegCirclePlay } from "react-icons/fa6";
import { useState } from "react";

interface Guideline {
  id: number;
  title: string;
  files: {
    id: number;
    title: string;
    attachment: string;
    file_type: string;
    description: string;
  }[];
}

export default function GuidelinesPage() {
  const { id } = useParams<{ id: string }>();
  const t = useTranslations();
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [selectedFile, setSelectedFile] = useState<
    null | Guideline["files"][0]
  >(null);

  const { data: guidelines, isLoading } = useQuery<Guideline[]>({
    queryKey: ["guidelines", id],
    queryFn: async () => {
      const response = await axiosInstance.get(`/participants/guidelines`, {
        params: {
          application_id: id,
        },
      });
      return response.data.data;
    },
  });

  if (isLoading) {
    return <Spin />;
  }

  if (!guidelines?.length) {
    return (
      <div className="dashboard-card">
        <Empty description={t("sorry-no-guidelines-yet")} />
      </div>
    );
  }

  return (
    <div>
      {guidelines &&
        guidelines.length > 0 &&
        guidelines.map((guide: any) => (
          <div key={guide.id} className="w-full mb-4">
            <Card>
              <h1 className="text-foreground font-bold text-xl mb-6">
                {guide.title}
              </h1>
              <Flex vertical gap={16}>
                {guide?.files &&
                  guide?.files?.map((file: any) => (
                    <Flex
                      key={file.id}
                      align="center"
                      justify="space-between"
                      className="py-1 gap-x-4 gap-y-2 max-sm:flex-wrap"
                    >
                      <Flex align="start" gap={4}>
                        <PiFiles
                          size={32}
                          className="text-primary flex-shrink-0"
                        />
                        <p className="text-[#626262] text-base font-medium min-h-8 flex items-center">
                          {file.title}
                        </p>
                      </Flex>
                      <Flex align="center" gap={12} className="max-sm:ms-8">
                        {file.file_type === "video" && (
                          <>
                            <button>
                              <FaRegCirclePlay
                                size={24}
                                className="text-primary"
                                onClick={() => setSelectedFile(file)}
                              />
                            </button>
                          </>
                        )}
                        <a
                          href={file?.attachment || ""}
                          target="_blank"
                          rel="noopener noreferrer"
                          download={file.title}
                        >
                          <MdDownload size={24} className="text-primary" />
                        </a>
                      </Flex>
                    </Flex>
                  ))}
              </Flex>
            </Card>
          </div>
        ))}
      <Modal
        title={selectedFile?.title}
        open={!!selectedFile}
        onCancel={() => setSelectedFile(null)}
        footer={null}
        destroyOnClose
        width={800}
      >
        <div className="bg-[#F2F4F7] item__wrapper my-4">
          {selectedFile?.attachment &&
          (selectedFile.attachment.includes("youtube") ||
            selectedFile.attachment.includes("drive.google")) ? (
            <iframe
              className="w-full h-[450px]"
              src={selectedFile.attachment}
              title={selectedFile.title}
              frameBorder="0"
              allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
              allowFullScreen
            />
          ) : (
            <video
              className="w-full h-auto min-h-[150px] max-h-[450px]"
              controls
              src={selectedFile?.attachment}
              onError={(e) => {
                const videoEl = e.currentTarget;
                videoEl.style.display = "none";
                const fallback = document.createElement("p");
                fallback.innerText = t("play-video-fail");
                fallback.className = "text-red-500 font-medium text-center py-4";
                videoEl.parentNode?.appendChild(fallback);
              }}
            />
          )}
        </div>
      </Modal>
    </div>
  );
}
