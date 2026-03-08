"use client";

import {
  Button,
  Checkbox,
  Form,
  Input,
  message,
  Modal,
  Select,
  Upload,
} from "antd";
import { useTranslations } from "next-intl";
import { useEffect, useState } from "react";
import { RiImageAddLine } from "react-icons/ri";
import { FiMinusCircle } from "react-icons/fi";

import { TbPencilMinus } from "react-icons/tb";
import { useMutation, useQuery } from "@tanstack/react-query";
import axiosInstance, { APIError } from "@/axios";
import FeedbackModal from "../feedback-modal/FeedbackModal";
import { useWatch } from "antd/es/form/Form";
import useSetFieldsErrors from "@/hooks/useSetFieldsErrors";
import { Program, Field, Team } from "@/lib/interfaces";
import { useRenderFieldType } from "@/hooks/useRenderField";
import { useGetValidationRules } from "@/hooks/useGetValidationRules";

export default function AddTeamModal({
  applicationId,
  programId,
  program,
  formConfig,
  isFormConfigLoading,
  isEdit,
  disabled,
  editData,
  refetch,
}: {
  applicationId: string;
  programId: string;
  program: Program | undefined;
  formConfig: any;
  isFormConfigLoading?: boolean;
  isEdit?: boolean;
  disabled?: boolean;
  editData?: Team;
  refetch: () => void;
}) {
  const t = useTranslations();
  const [messageApi, contextHolder] = message.useMessage();
  const [openModal, setOpenModal] = useState(false);
  const [successModal, setSuccessModal] = useState(false);
  const [form] = Form.useForm();
  const [needMoreMembers, setNeedMoreMembers] = useState(false);
  const setFieldsErrors = useSetFieldsErrors(form);
  const [subTrackOptions, setSubTrackOptions] = useState<
    { label: string; value: number }[]
  >([]);
  const { renderFieldType } = useRenderFieldType();
  const { getValidationRules } = useGetValidationRules();

  // teamFelid
  const teamFelid: Field = {
    id: "add_team_serial_numbers",
    slug: "serial_numbers",
    label: t("members-ids"),
    type: "team",
    required: true,
    validation_rules: [],
    placeholder: t("members-ids"),
    fieldClass: "static_field",
  };

  // add team
  const { mutate, isPending } = useMutation({
    mutationFn: async (data: any) => {
      const response = await axiosInstance.post(`/participants/teams`, data, {
        params: {
          application_id: applicationId,
        },
        headers: {
          "Content-Type": "multipart/form-data",
        },
      });
      return response.data;
    },

    onSuccess: () => {
      setSuccessModal(true);
    },

    onError: (error: APIError) => {
      setFieldsErrors(error);
    },
  });
  const onSubmit = async (data: any) => {
    const formData = new FormData();
    const skills =
      data?.skills && needMoreMembers === true
        ? data.skills.filter((skill: string | undefined) => skill !== undefined)
        : undefined;

    const payload = {
      ...data,
      contact_email: needMoreMembers ? data.contact_email : undefined,
      skills,
    };

    // Apply the payload to the form data
    Object.keys(payload).forEach((key) => {
      const value = payload[key];
      if (value != null) {
        if (Array.isArray(value) && key != "serial_numbers") {
          if (value[0]?.originFileObj) {
            formData.append(key, value[0].originFileObj);
          } else {
            value.forEach((item: any, index: number) => {
              formData.append(`${key}[${index}]`, item);
            });
          }
        } else {
          formData.append(key, payload[key]);
        }
      }
    });

    mutate(formData as any);
  };

  // edit team
  const { mutate: editMutate, isPending: isEditPending } = useMutation({
    mutationFn: async (data: any) => {
      const response = await axiosInstance.post(
        `/participants/teams/${editData?.id}`,
        data,
        {
          params: {
            application_id: applicationId,
            _method: "PUT",
          },
          headers: {
            "Content-Type": "multipart/form-data",
          },
        }
      );
      return response.data;
    },
    onSuccess: () => {
      setSuccessModal(true);
    },
    onError: (error: APIError) => {
      setFieldsErrors(error);
      messageApi.error(error.response.data.message);
    },
  });
  const onSubmitEdit = async (data: any) => {
    const formData = new FormData();
    const payload = {
      ...data,
    };
    
    // Apply the payload to the form data
    Object.keys(payload).forEach((key) => {
      const value = payload[key];
      if (value != null) {
        if (Array.isArray(value) && key != "serial_numbers") {
          if (value[0]?.originFileObj) {
            formData.append(key, value[0].originFileObj);
          } else {
            value.forEach((item: any, index: number) => {
              formData.append(`${key}[${index}]`, item);
            });
          }
        } else {
          formData.append(key, payload[key]);
        }
      }
    });

    editMutate(formData);
  };

  // get sub_tracks
  function getSubTracks(selectedTrackId: any) {
    const selectedTrack = program?.tracks?.find(
      (track) => track.id === selectedTrackId
    );
    const subTrack =
      selectedTrack?.sub_tracks?.map((sub) => ({
        label: sub.name,
        value: sub.id,
      })) || [];
    setSubTrackOptions(subTrack);
  }

  // add sub_tracks
  useEffect(() => {
    if (!program?.id) return;
    const selectedTrackId =
      editData?.track?.id ||
      program?.tracks.find((track) => track.is_selected)?.id;
    getSubTracks(selectedTrackId);
  }, [program, editData]);

  // reset fields
  useEffect(() => {
    if (!program?.id || !formConfig?.id) return;
    if (!isEdit) {
      form.resetFields();
    }
  }, [program, formConfig, openModal, isEdit]);

  return (
    <>
      {contextHolder}
      <Button
        onClick={() => setOpenModal(true)}
        type={isEdit ? "default" : "primary"}
        className={`!px-4 ${isEdit ? "!bg-[#0A0F290A] !border-none" : ""}`}
        icon={isEdit ? <TbPencilMinus /> : undefined}
        iconPosition="end"
        disabled={disabled}
      >
        {isEdit ? t("edit-team") : t("create-a-new-team")}
      </Button>
      <FeedbackModal
        openModal={successModal}
        title={
          isEdit ? t("team-edited-successfully") : t("team-added-successfully")
        }
        subtitle={
          isEdit
            ? undefined
            : t("the-team-will-be-automatically-added-to-members-accounts")
        }
        type="success"
        onBtnClick={() => {
          refetch();
          setSuccessModal(false);
          setOpenModal(false);
        }}
      />
      <Modal
        open={openModal && !successModal}
        onCancel={() => {
          setOpenModal(false);
          form.resetFields();
        }}
        footer={null}
        forceRender
        style={{ top: 20 }}
      >
        <div className="flex flex-col gap-y-10 py-6 px-4">
          <h1 className="text-[#5B656A] text-2xl font-bold m-0">
            {isEdit ? t("edit-team") : t("create-a-new-team")}
          </h1>

          <Form
            layout="vertical"
            form={form}
            onFinish={isEdit ? onSubmitEdit : onSubmit}
          >
            <Form.Item
              label={t("team-image")}
              name={"logo"}
              rules={[
                {
                  validator: (_, value) => {
                    if (!value || !value.length) return Promise.resolve();

                    const file = value[0];

                    if (file.url || file.thumbUrl) return Promise.resolve();

                    if (
                      ![
                        "image/png",
                        "image/jpg",
                        "image/jpeg",
                        "image/webp",
                      ].includes(file.type)
                    ) {
                      return Promise.reject(
                        t(
                          "the-attached-image-must-be-in-png-jpg-jpeg-or-webp-format"
                        )
                      );
                    }

                    if (file.size > 1024 * 1024) {
                      return Promise.reject(
                        t("uploaded-image-must-not-exceed-1-mb")
                      );
                    }

                    return Promise.resolve();
                  },
                },
              ]}
              valuePropName="fileList"
              getValueFromEvent={(e) => e?.fileList || []}
            >
              <Upload
                accept="image/*"
                maxCount={1}
                listType="picture-circle"
                className="avatar-uploader"
                beforeUpload={() => false}
                showUploadList={{
                  showPreviewIcon: false,
                  showRemoveIcon: true,
                }}
                defaultFileList={
                  editData?.logo
                    ? [
                        {
                          url: editData?.logo,
                          name: editData?.logo,
                          uid: "1",
                        },
                      ]
                    : []
                }
              >
                <div className="flex items-center justify-center text-[#98A2B3] text-2xl">
                  <RiImageAddLine />
                </div>
              </Upload>
            </Form.Item>

            <Form.Item
              label={t("team-name")}
              name={"name"}
              initialValue={editData?.name}
              rules={[
                {
                  required: true,
                },
              ]}
            >
              <Input placeholder={t("enter-team-name")} />
            </Form.Item>

            {program?.tracks && program?.tracks?.length > 0 && (
              <>
                <Form.Item
                  label={t("track")}
                  name={"track_id"}
                  initialValue={
                    editData?.track?.id ||
                    program.tracks.find((track) => track.is_selected)?.id
                  }
                  rules={[
                    {
                      required: true,
                    },
                  ]}
                >
                  <Select
                    placeholder={t("choose")}
                    notFoundContent={null}
                    showSearch={true}
                    allowClear={true}
                    options={program.tracks?.map((track) => ({
                      label: track.name,
                      value: track.id,
                    }))}
                    filterOption={(input, option) =>
                      (option?.label ?? "")
                        .toString()
                        .toLowerCase()
                        .includes(input.toLowerCase())
                    }
                    onChange={(selectedTrackId) => {
                      getSubTracks(selectedTrackId);
                      form.setFieldsValue({ sub_track_id: undefined });
                    }}
                    disabled={!formConfig?.allow_track_selection}
                  />
                </Form.Item>
                <Form.Item
                  label={t("sub-track")}
                  name={"sub_track_id"}
                  initialValue={
                    editData?.sub_track?.id ||
                    program.tracks
                      .find((track) => track.is_selected)
                      ?.sub_tracks?.find((subTrack) => subTrack.is_selected)?.id
                  }
                >
                  <Select
                    placeholder={t("choose")}
                    notFoundContent={null}
                    showSearch={true}
                    allowClear={true}
                    options={subTrackOptions}
                    filterOption={(input, option) =>
                      (option?.label ?? "")
                        .toString()
                        .toLowerCase()
                        .includes(input.toLowerCase())
                    }
                    disabled={
                      !formConfig?.allow_track_selection ||
                      !subTrackOptions.length
                    }
                  />
                </Form.Item>
              </>
            )}

            <Form.Item
              label={t("project-brief")}
              name={"idea_description"}
              initialValue={editData?.idea_description}
              rules={[
                {
                  required: true,
                },
                {
                  max: 300,
                  message: t("maximum-brief-length-is-300-characters"),
                },
              ]}
            >
              <Input.TextArea rows={4} placeholder={t("enter-project-brief")} />
            </Form.Item>

            {!isEdit && (
              <Form.Item
                label={t("members-ids")}
                name={"serial_numbers"}
                className="!mb-1"
                rules={getValidationRules({
                  ...teamFelid,
                  validation_rules: [
                    {
                      rule: "max_team_members",
                      value: Number(formConfig?.max_team_members),
                    },
                    {
                      rule: "min_team_members",
                      value: Number(formConfig?.min_team_members),
                    },
                  ],
                })}
              >
                {renderFieldType(teamFelid)}
              </Form.Item>
            )}

            {!isEdit && (
              <Checkbox
                className="!my-5 !text-sm !font-medium"
                checked={needMoreMembers}
                onChange={(e) => {
                  setNeedMoreMembers(e.target.checked);
                }}
              >
                {t("need-additional-members")}
              </Checkbox>
            )}

            {needMoreMembers && (
              <>
                <Form.List name="skills" initialValue={[""]}>
                  {(fields, { add, remove }) => (
                    <>
                      {fields?.map(({ key, name, ...restField }) => (
                        <div
                          className="flex items-center gap-x-2 w-full"
                          key={key}
                        >
                          <Form.Item
                            className="flex-1"
                            {...restField}
                            label={t("skill") + " " + (name + 1)}
                            name={name} // Remove the nested array structure
                            rules={[{ required: true }]}
                          >
                            <Input
                              placeholder={t("the-skill-needed-by-the-team")}
                            />
                          </Form.Item>
                          <FiMinusCircle
                            onClick={() => remove(name)}
                            className="text lg cursor-pointer"
                          />
                        </div>
                      ))}
                      <Form.Item>
                        <button
                          className="bg-[#0A0F290A] text-[#5B656A] border-none py-3 px-4 text-sm font-medium cursor-pointer"
                          onClick={() => add()}
                          type="button"
                        >
                          {t("add-another-skill")}
                        </button>
                      </Form.Item>
                    </>
                  )}
                </Form.List>
                <Form.Item
                  label={t("contact-email")}
                  name={"contact_email"}
                  rules={[
                    {
                      type: "email",
                    },
                  ]}
                >
                  <Input placeholder={t("enter-the-email")} />
                </Form.Item>
              </>
            )}

            <Button
              type="primary"
              htmlType="submit"
              loading={isEdit ? isEditPending : isPending}
              block
              onClick={() => {
                if (isEdit) {
                  return;
                }

                const logo = form.getFieldValue("logo");

                if (logo == null) {
                  return form.setFields([
                    {
                      name: "logo",
                      errors: [t("required")],
                    },
                  ]);
                }
              }}
            >
              {t("send")}
            </Button>
          </Form>
        </div>
      </Modal>
    </>
  );
}
