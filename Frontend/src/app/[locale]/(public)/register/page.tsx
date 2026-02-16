"use client";

import { useLocale, useTranslations } from "next-intl";
import { Link, useRouter } from "@/i18n/routing";
import {
  Button,
  Checkbox,
  DatePicker,
  Divider,
  Form,
  Input,
  message,
  Select,
  Spin,
  Steps,
} from "antd";
import { useSearchParams } from "next/navigation";
import { FaRegUser } from "react-icons/fa";
import { MdOutlineMailOutline } from "react-icons/md";
import LabelIcon from "@/components/LabelIcon";
import moment, { Moment } from "moment";
import { useMutation, useQuery } from "@tanstack/react-query";
import axiosInstance, { APIError } from "@/axios";
import useSetFieldsErrors from "@/hooks/useSetFieldsErrors";
import FeedbackModal from "@/components/feedback-modal/FeedbackModal";
import PhoneInput from "react-phone-input-2";
import "react-phone-input-2/lib/style.css";
import { useState } from "react";
import NafathLogin from "@/components/auth/NafathLogin";

const formKeys = {
  name: "name",
  email: "email",
  phone: "phone",
  gender: "gender",
  date_of_birth: "date_of_birth",
  nationality: "nationality_id",
  country: "country_id",
  residence_city: "residence_city_id",
  password: "password",
  educational_background: "educational_background",
  current_role: "current_role",
  place_of_work_study: "place_of_work_study",
  years_of_experience: "years_of_experience",
  experience_or_skills: "experience_or_skills",
  key_achievements: "key_achievements",
};

const formKeys1 = [
  formKeys.name,
  formKeys.email,
  formKeys.phone,
  formKeys.gender,
  formKeys.date_of_birth,
  formKeys.nationality,
  formKeys.country,
  formKeys.residence_city,
  formKeys.password,
];

type FormValues = {
  name: string;
  email: string;
  phone: string;
  gender: string;
  date_of_birth: Moment;
  nationality: string;
  country: string;
  residence_city: string;
  password: string;
  educational_background: string;
  current_role: string;
  place_of_work_study: string;
  years_of_experience: string;
  experience_or_skills: string;
  key_achievements: string;
};

const educationalBackground = [
  {
    key: "high_school",
    title: {
      en: "High school",
      ar: "مرحلة ثانوية",
    },
  },
  {
    key: "diploma",
    title: {
      en: "Diploma",
      ar: "دبلوم",
    },
  },
  {
    key: "bachelor",
    title: {
      en: "Bachelor's",
      ar: "بكالوريوس",
    },
  },
  {
    key: "master",
    title: {
      en: "Master's",
      ar: "ماجستير",
    },
  },
  {
    key: "phd",
    title: {
      en: "PhD",
      ar: "دكتوراه",
    },
  },
];

const currentRole = [
  {
    key: "high_school_student",
    title: {
      en: "High school student",
      ar: "طالب في المرحلة الثانوية",
    },
  },
  {
    key: "university_student",
    title: {
      en: "University student",
      ar: "طالب جامعي",
    },
  },
  {
    key: "recently_graduated",
    title: {
      en: "Recently graduated",
      ar: "حديث تخرج",
    },
  },
  {
    key: "private_sector_employee",
    title: {
      en: "Private sector employee",
      ar: "موظف قطاع خاص",
    },
  },
  {
    key: "government_sector_employee",
    title: {
      en: "Government sector employee",
      ar: "موظف قطاع حكومي",
    },
  },
  {
    key: "non_profit_sector_employee",
    title: {
      en: "Non-profit sector employee",
      ar: "موظف قطاع غير ربحي",
    },
  },
  {
    key: "freelancer",
    title: {
      en: "Freelancer",
      ar: "عمل حر",
    },
  },
  {
    key: "unemployed",
    title: {
      en: "I don't work",
      ar: "لا أعمل",
    },
  },
];

const yearsOfExperience = [
  {
    key: "less_than_one",
    title: {
      en: "Less than one year",
      ar: "أقل من سنة",
    },
  },
  {
    key: "one_to_three",
    title: {
      en: "From 1 to 3 years",
      ar: "من 1 إلى 3 سنوات",
    },
  },
  {
    key: "three_to_five",
    title: {
      en: "From 3 to 5 years",
      ar: "من 3 إلى 5 سنوات",
    },
  },
  {
    key: "five_to_ten",
    title: {
      en: "From 5 to 10 years",
      ar: "من 5 إلى 10 سنوات",
    },
  },
  {
    key: "more_than_ten",
    title: {
      en: "More than 10 years",
      ar: "أكثر من 10 سنوات",
    },
  },
  {
    key: "no_experience",
    title: {
      en: "No experience",
      ar: "لا يوجد خبرة",
    },
  },
];

interface Country {
  id: number;
  name: string;
}

interface City {
  id: number;
  name: string;
}

interface Nationality {
  id: number;
  name: string;
}

export default function RegisterPage() {
  const t = useTranslations();
  const [form] = Form.useForm();
  const searchParams = useSearchParams();
  const router = useRouter();
  const locale = useLocale() as "en" | "ar";
  const [messageApi, contextHolder] = message.useMessage();

  const currentStep = searchParams.has("step")
    ? parseInt(searchParams.get("step") as string)
    : 0;

  const success = searchParams.get("success") === "true";

  const setFieldsErrors = useSetFieldsErrors(form);

  const country_id = Form.useWatch(formKeys.country, form);

  const privacy_policy = Form.useWatch("privacy_policy", form);

  // get login methods
  const { data: loginMethods, isLoading: isLoginMethodsLoading } = useQuery({
    queryKey: ["login-methods"],
    queryFn: async () => {
      const response = await axiosInstance.get("/nafath/login-methods");
      return response.data;
    },
    retry:false,
  });

  const { data: countries } = useQuery<Country[]>({
    queryKey: ["countries"],
    queryFn: async () => {
      const response = await axiosInstance.get(`/countries`);
      return response.data.data;
    },
  });

  const { data: cities } = useQuery<City[]>({
    queryKey: ["cities", country_id],
    queryFn: async () => {
      const response = await axiosInstance.get(`/cities`, {
        params: {
          country_id: country_id,
        },
      });
      return response.data.data;
    },
    enabled: !!country_id,
  });

  const { data: nationalities } = useQuery<Nationality[]>({
    queryKey: ["nationalities"],
    queryFn: async () => {
      const response = await axiosInstance.get(`/nationalities`);
      return response.data.data;
    },
  });

  const { mutate, isPending } = useMutation({
    mutationFn: async (values: unknown) => {
      const response = await axiosInstance.post(
        "/participants/auth/register",
        values
      );

      return response.data;
    },
    onSuccess: () => {
      router.push(`?success=true`);
    },
    onError: (error: APIError) => {
      setFieldsErrors(error);

      const errKeys = Object.keys(error.response.data.errors || {});

      if (errKeys.some((key) => formKeys1.includes(key))) {
        router.push(`?step=${0}`);
      }
    },
  });

  const onSubmit = async (values: any) => {
    mutate({
      ...values,
      date_of_birth: values.date_of_birth.format("YYYY-MM-DD"),
    });
  };

  // Define a class based on the locale
  const phoneInputClass = locale === "ar" ? "phone-input-ar" : "phone-input-en";

  if (isLoginMethodsLoading) {
    return <Spin className="py-10" />;
  }

  return (
    <>
      {contextHolder}
      <div className="card p-0">
        <div className="pb-10 pt-10 px-5 md:px-10">
          <div className="mb-8">
            <p className="text-primary text-xl text-center sm:text-start">
              {t("welcome")}
            </p>
            <h1 className="text-4xl text-[#5B656A] font-bold text-center sm:text-start">
              {t("create-account")}
            </h1>
          </div>

          {loginMethods?.nafath_available && (
            <div className="nafath-login-wrapper">
              <NafathLogin role="participant" btnText={t("nafath.signup")} />
            </div>
          )}

          {loginMethods?.nafath_available &&
            loginMethods?.regular_available && (
              <Divider className="!my-8">{t("or-use")}</Divider>
            )}
          {(loginMethods?.regular_available || !loginMethods) && (
            <>
              <Steps
                className="!mb-0 register-steps"
                current={currentStep}
                labelPlacement="vertical"
                size="default"
                responsive
                items={[
                  {
                    title: t("personal-information"),
                    status: currentStep === 0 ? "process" : "wait",
                  },
                  {
                    title: t("experiences"),
                    status: currentStep === 1 ? "process" : "wait",
                  },
                ]}
              />
              <Form layout="vertical" onFinish={onSubmit} form={form} className="pt-8">
                <div
                  className={`grid grid-cols-1 md:grid-cols-2 gap-4 ${
                    currentStep === 0 ? "block" : "hidden"
                  }`}
                >
                  {/* <h2 className="mb-4 md:col-span-2 text-base font-medium">
                {t("personal-information")}
              </h2> */}
                  <Form.Item
                    label={t("full-name")}
                    name={formKeys.name}
                    hasFeedback
                    required
                    rules={[
                      {
                        required: true,
                      },
                    ]}
                  >
                    <Input
                      placeholder={t("full-name-matches-id")}
                      prefix={<LabelIcon icon={<FaRegUser />} />}
                    />
                  </Form.Item>
                  <Form.Item
                    label={t("email")}
                    name={formKeys.email}
                    hasFeedback
                    required
                    rules={[
                      {
                        required: true,
                      },
                      {
                        type: "email",
                      },
                    ]}
                  >
                    <Input
                      placeholder="Email@domain.com"
                      prefix={<LabelIcon icon={<MdOutlineMailOutline />} />}
                    />
                  </Form.Item>

                  <Form.Item
                    label={t("phone-number")}
                    name={formKeys.phone}
                    hasFeedback
                    required
                    rules={[
                      {
                        required: true,
                      },
                      {
                        validator: (_, value) => {
                          if (!value || value.length !== 12) {
                            return Promise.reject(
                              new Error(
                                t("Phone number entered is not correct")
                              )
                            );
                          }
                          return Promise.resolve();
                        },
                      },
                    ]}
                  >
                    <PhoneInput
                      country={"sa"}
                      placeholder="500000000"
                      enableSearch
                      inputStyle={{
                        width: "100%",
                        direction: locale === "ar" ? "ltr" : "ltr",
                        paddingRight: locale === "ar" ? "74px" : "0px",
                        textAlign: locale === "ar" ? "right" : "left",
                      }}
                      buttonStyle={{
                        paddingRight: locale === "ar" ? "28px" : "0px",
                      }}
                      containerClass={phoneInputClass}
                    />
                  </Form.Item>

                  <Form.Item
                    label={t("gender")}
                    name={formKeys.gender}
                    hasFeedback
                    required
                    rules={[
                      {
                        required: true,
                      },
                    ]}
                  >
                    <Select
                      placeholder={t("choose")}
                      prefix={<LabelIcon icon={<FaRegUser />} />}
                    >
                      <Select.Option value={"male"}>{t("male")}</Select.Option>
                      <Select.Option value={"female"}>
                        {t("female")}
                      </Select.Option>
                    </Select>
                  </Form.Item>

                  <Form.Item
                    label={t("date-of-birth")}
                    name={formKeys.date_of_birth}
                    hasFeedback
                    required
                    rules={[
                      {
                        required: true,
                      },
                    ]}
                  >
                    <DatePicker
                      format="DD-MM-YYYY"
                      placeholder={t("choose")}
                      disabledDate={(current) =>
                        current &&
                        (current < moment("1950-01-01") ||
                          current > moment().subtract(10, "years"))
                      }
                    />
                  </Form.Item>

                  <Form.Item
                    name={formKeys.nationality}
                    hasFeedback
                    required
                    rules={[
                      {
                        required: true,
                      },
                    ]}
                    label={t("nationality")}
                  >
                    <Select placeholder={t("choose")}>
                      {nationalities?.map((nationality) => (
                        <Select.Option
                          key={nationality.id}
                          value={nationality.id}
                        >
                          {nationality.name}
                        </Select.Option>
                      ))}
                    </Select>
                  </Form.Item>

                  <Form.Item
                    name={formKeys.country}
                    hasFeedback
                    required
                    rules={[
                      {
                        required: true,
                      },
                    ]}
                    label={t("country")}
                  >
                    <Select
                      placeholder={t("choose")}
                      onChange={() => {
                        form.setFieldValue(formKeys.residence_city, undefined);
                      }}
                    >
                      {countries?.map((country) => (
                        <Select.Option key={country.id} value={country.id}>
                          {country.name}
                        </Select.Option>
                      ))}
                    </Select>
                  </Form.Item>

                  <Form.Item
                    name={formKeys.residence_city}
                    hasFeedback
                    required
                    rules={[
                      {
                        required: true,
                      },
                    ]}
                    label={t("residence-of-city")}
                  >
                    <Select placeholder={t("choose")}>
                      {cities?.map((city) => (
                        <Select.Option key={city.id} value={city.id}>
                          {city.name}
                        </Select.Option>
                      ))}
                    </Select>
                  </Form.Item>

                  <Form.Item
                    name={formKeys.password}
                    label={t("password")}
                    rules={[
                      {
                        required: true,
                      },
                      {
                        pattern:
                          /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*_-]).{12,}$/,
                        message: t(
                          "the-password-must-contain-at-least-12-characters-including-an-uppercase-letter-a-lowercase-letter-a-number-and-a-symbol"
                        ),
                      },
                    ]}
                    hasFeedback
                  >
                    <Input.Password placeholder={t("password")} />
                  </Form.Item>

                  <Form.Item
                    name="confirm"
                    label={t("confirm-password")}
                    dependencies={["password"]}
                    hasFeedback
                    rules={[
                      {
                        required: true,
                      },
                      ({ getFieldValue }) => ({
                        validator(_, value) {
                          if (!value || getFieldValue("password") === value) {
                            return Promise.resolve();
                          }
                          return Promise.reject(
                            new Error(t("password-does-not-match"))
                          );
                        },
                      }),
                    ]}
                  >
                    <Input.Password placeholder={t("confirm-password")} />
                  </Form.Item>
                </div>

                <div
                  className={`grid grid-cols-1 md:grid-cols-2 gap-4 ${
                    currentStep === 1 ? "block" : "hidden"
                  }`}
                >
                  {/* <h2 className="mb-4 md:col-span-2 text-base font-medium">
                {t("experiences")}
              </h2> */}

                  <Form.Item
                    label={t("educational-background")}
                    name={formKeys.educational_background}
                    rules={[
                      {
                        required: true,
                      },
                    ]}
                  >
                    <Select placeholder={t("choose")}>
                      {educationalBackground.map((item, index) => (
                        <Select.Option key={index} value={item.key}>
                          {item.title[locale]}
                        </Select.Option>
                      ))}
                    </Select>
                  </Form.Item>

                  <Form.Item
                    label={t("your-current-role")}
                    name={formKeys.current_role}
                    rules={[
                      {
                        required: true,
                      },
                    ]}
                  >
                    <Select placeholder={t("choose")}>
                      {currentRole.map((item, index) => (
                        <Select.Option key={index} value={item.key}>
                          {item.title[locale]}
                        </Select.Option>
                      ))}
                    </Select>
                  </Form.Item>

                  <Form.Item
                    label={t("place-of-work-study")}
                    name={formKeys.place_of_work_study}
                    rules={[
                      {
                        required: true,
                      },
                    ]}
                  >
                    <Input
                      placeholder={t("place-of-work-study")}
                      prefix={<LabelIcon icon={<FaRegUser />} />}
                    />
                  </Form.Item>

                  <Form.Item
                    label={t("years-of-experience")}
                    name={formKeys.years_of_experience}
                    rules={[
                      {
                        required: true,
                      },
                    ]}
                  >
                    <Select placeholder={t("choose")}>
                      {yearsOfExperience.map((item, index) => (
                        <Select.Option key={index} value={item.key}>
                          {item.title[locale]}
                        </Select.Option>
                      ))}
                    </Select>
                  </Form.Item>

                  <Form.Item
                    label={t("experience-or-skills")}
                    name={formKeys.experience_or_skills}
                    rules={[
                      {
                        required: true,
                      },
                      {
                        max: 1500,
                      },
                    ]}
                  >
                    <Input.TextArea
                      placeholder={t("tell-us-about-your-experience-or-skills")}
                      autoSize={{ minRows: 3, maxRows: 6 }}
                    />
                  </Form.Item>

                  <Form.Item
                    label={t("key-achievements")}
                    name={formKeys.key_achievements}
                    rules={[
                      {
                        required: false,
                      },
                      {
                        max: 1500,
                      },
                    ]}
                  >
                    <Input.TextArea
                      placeholder={t("achievements-or-indicators-of-success")}
                      autoSize={{ minRows: 3, maxRows: 6 }}
                    />
                  </Form.Item>

                  <Form.Item
                    name={"privacy_policy"}
                    valuePropName="checked"
                    rules={[
                      {
                        required: true,
                      },
                    ]}
                  >
                    <Checkbox>
                      {t("i-agree-to-the")}{" "}
                      <Link
                        href={"/privacy-policy"}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="text-primary-900 underline"
                      >
                        {t("privacy-policy")}
                      </Link>
                    </Checkbox>
                  </Form.Item>
                </div>

                <div className="flex justify-between items-center">
                  {currentStep === 1 && (
                    <Button
                      type="default"
                      htmlType="button"
                      size="large"
                      disabled={isPending}
                      onClick={() => router.push(`?step=${currentStep - 1}`)}
                    >
                      {t("previous")}
                    </Button>
                  )}
                  {currentStep === 0 && (
                    <Button
                      type="default"
                      htmlType="button"
                      size="large"
                      className="ms-auto"
                      onClick={() => {
                        form
                          .validateFields([...formKeys1, "confirm"])
                          .then(() => {
                            router.push(`?step=${currentStep + 1}`);
                          })
                          .catch(() => {});
                      }}
                    >
                      {t("next")}
                    </Button>
                  )}
                  {currentStep === 1 && (
                    <Button
                      type="primary"
                      htmlType={"submit"}
                      size="large"
                      loading={isPending}
                      disabled={!privacy_policy}
                    >
                      {t("sign-up")}
                    </Button>
                  )}
                </div>
              </Form>
              <div className=" mt-6 flex justify-center items-center gap-5 bg-[#EAECF0] !py-6  px-[20px] sm:px-[40px] rounded-bl-2xl rounded-br-2xl border border-solid border-[#D0D5DD]">
                <span className="text-[#586368]">
                  {t("youve-an-account-already")}
                </span>
                <Link href="/login">
                  <Button type="default" htmlType="button" size="large">
                    {t("login")}
                  </Button>
                </Link>
              </div>
            </>
          )}
        </div>
      </div>

      <FeedbackModal
        openModal={success}
        title={t("account-created-successfully")}
        subtitle={t(
          "please-activate-your-account-through-the-link-sent-to-your-email-address"
        )}
        type="success"
        btnLabel={t("login")}
        onBtnClick={() => {
          router.push("/login");
        }}
      />
    </>
  );
}
